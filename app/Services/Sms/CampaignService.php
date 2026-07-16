<?php

namespace App\Services\Sms;

use App\Enums\CampaignRecipientStatus;
use App\Enums\CampaignStatus;
use App\Enums\SmsMessageStatus;
use App\Enums\SmsProviderDriver;
use App\Exceptions\BusinessException;
use App\Jobs\Sms\ProcessCampaignChunkJob;
use App\Jobs\Sms\SendSmsBatchJob;
use App\Models\SmsCampaign;
use App\Models\SmsCampaignRecipient;
use App\Models\User;
use App\Repositories\Contracts\SmsMessageRepositoryInterface;
use App\Repositories\Contracts\SmsProviderRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contact\ContactService;
use App\Services\Contracts\UserSenderNumberServiceInterface;
use App\Services\Contracts\WalletServiceInterface;
use App\Sms\Support\SmsSegmentCalculator;
use App\Support\UserScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CampaignService
{
    public function __construct(
        private readonly ContactService $contactService,
        private readonly UserRepositoryInterface $userRepository,
        private readonly WalletServiceInterface $walletService,
        private readonly UserSenderNumberServiceInterface $userSenderNumberService,
        private readonly SmsMessageRepositoryInterface $smsMessageRepository,
        private readonly SmsProviderRepositoryInterface $smsProviderRepository,
        private readonly SmsSegmentCalculator $segmentCalculator,
    ) {}

    public function list(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = SmsCampaign::query()->with('user');

        if (! UserScope::isPlatformAdmin($user)) {
            $query->where('user_id', $user->id);
        } elseif (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): SmsCampaign
    {
        $contacts = $this->contactService->getActiveForUser(
            $user,
            ! empty($data['contact_ids']) ? array_map('intval', (array) $data['contact_ids']) : null,
        );

        if ($contacts->isEmpty()) {
            throw new BusinessException('Gönderim için en az bir aktif rehber kaydı seçin.');
        }

        $maxRecipients = (int) config('sms.campaign.max_recipients', 200000);

        if ($contacts->count() > $maxRecipients) {
            throw new BusinessException("En fazla {$maxRecipients} alıcıya kampanya gönderilebilir.");
        }

        $segments = $this->segmentCalculator->calculateSegments($data['message']);
        $provider = $this->smsProviderRepository->findDefaultActive();
        $usesEasySendSms = $provider?->driver === SmsProviderDriver::EasySendSms
            || ($provider === null && config('sms.default_provider') === SmsProviderDriver::EasySendSms->value);

        if ($usesEasySendSms && $segments > 5) {
            throw new BusinessException('EasySendSMS mesajları en fazla 5 SMS segmenti olabilir.');
        }

        $totalCredits = $segments * $contacts->count();

        if ((float) $this->walletService->getAvailableBalance($user) < $totalCredits) {
            throw new BusinessException("Yetersiz SMS hakkı. Gerekli: {$totalCredits} adet.");
        }

        $senderId = $this->userSenderNumberService->resolveSenderId($user, $data['sender_id'] ?? null);
        $batchId = (string) Str::uuid();
        $chunkSize = (int) config('sms.campaign.chunk_size', 500);
        $chunkDelay = (int) config('sms.campaign.chunk_delay_seconds', 1);

        return DB::transaction(function () use ($user, $data, $contacts, $senderId, $batchId, $chunkSize, $chunkDelay): SmsCampaign {
            $campaign = SmsCampaign::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'message' => $data['message'],
                'sender_id' => $senderId,
                'status' => CampaignStatus::Pending,
                'total_recipients' => $contacts->count(),
                'chunk_size' => $chunkSize,
                'chunk_delay_seconds' => $chunkDelay,
                'batch_id' => $batchId,
            ]);

            $rows = $contacts->map(fn ($contact) => [
                'sms_campaign_id' => $campaign->id,
                'phone' => $contact->phone,
                'name' => $contact->name,
                'status' => CampaignRecipientStatus::Pending->value,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            foreach (array_chunk($rows, 1000) as $chunk) {
                SmsCampaignRecipient::insert($chunk);
            }

            $scheduledAt = ! empty($data['scheduled_at']) ? \Carbon\Carbon::parse($data['scheduled_at']) : null;

            if ($scheduledAt) {
                $campaign->update(['scheduled_at' => $scheduledAt]);
            }

            if ($scheduledAt && $scheduledAt->isFuture()) {
                ProcessCampaignChunkJob::dispatch($campaign->id)->delay($scheduledAt);
            } else {
                ProcessCampaignChunkJob::dispatch($campaign->id);
            }

            return $campaign->fresh(['user']);
        });
    }

    public function processChunk(int $campaignId): void
    {
        $campaign = SmsCampaign::query()->find($campaignId);

        if ($campaign === null || in_array($campaign->status, [CampaignStatus::Cancelled, CampaignStatus::Completed, CampaignStatus::Failed], true)) {
            return;
        }

        if ($campaign->scheduled_at && $campaign->scheduled_at->isFuture()) {
            ProcessCampaignChunkJob::dispatch($campaign->id)->delay($campaign->scheduled_at);

            return;
        }

        if ($campaign->status === CampaignStatus::Pending) {
            $campaign->update([
                'status' => CampaignStatus::Processing,
                'started_at' => now(),
            ]);
        }

        $recipients = SmsCampaignRecipient::query()
            ->where('sms_campaign_id', $campaign->id)
            ->where('status', CampaignRecipientStatus::Pending)
            ->limit($campaign->chunk_size)
            ->get();

        if ($recipients->isEmpty()) {
            $campaign->update([
                'status' => CampaignStatus::Completed,
                'completed_at' => now(),
            ]);

            return;
        }

        $user = $this->userRepository->findByIdOrFail($campaign->user_id);
        $segments = $this->segmentCalculator->calculateSegments($campaign->message);
        $providerCode = $this->smsProviderRepository->findDefaultActive()?->code ?? config('sms.default_provider', 'mock');
        $queuedMessageIds = [];

        foreach ($recipients as $recipient) {
            try {
                $queuedMessageIds[] = DB::transaction(function () use ($user, $campaign, $recipient, $segments, $providerCode): int {
                    $lockedUser = $this->userRepository->findByIdOrFail($user->id);

                    if ((float) $this->walletService->getAvailableBalance($lockedUser) < $segments) {
                        throw new BusinessException('Yetersiz SMS hakkı.');
                    }

                    $this->walletService->debit(
                        $lockedUser,
                        (float) $segments,
                        "Kampanya SMS ({$segments} segment): {$recipient->phone}",
                    );

                    $smsMessage = $this->smsMessageRepository->create([
                        'user_id' => $lockedUser->id,
                        'organization_id' => $lockedUser->organization_id,
                        'batch_id' => $campaign->batch_id,
                        'recipient' => $recipient->phone,
                        'message' => $campaign->message,
                        'sender_id' => $campaign->sender_id,
                        'provider' => $providerCode,
                        'status' => SmsMessageStatus::Queued->value,
                        'segments' => $segments,
                        'cost' => $segments,
                    ]);

                    $recipient->update([
                        'status' => CampaignRecipientStatus::Queued,
                        'sms_message_id' => $smsMessage->id,
                    ]);

                    return $smsMessage->id;
                });

                $campaign->increment('success_count');
            } catch (\Throwable $e) {
                $recipient->update([
                    'status' => CampaignRecipientStatus::Failed,
                    'error_message' => Str::limit($e->getMessage(), 250),
                ]);
                $campaign->increment('failed_count');
            }

            $campaign->increment('processed_count');
        }

        foreach (array_chunk($queuedMessageIds, SendSmsBatchJob::MAX_MESSAGES) as $chunkIndex => $messageIds) {
            SendSmsBatchJob::dispatch($messageIds)
                ->delay(now()->addSeconds(intdiv($chunkIndex, 25)));
        }

        $campaign->refresh();

        if ($campaign->recipients()->where('status', CampaignRecipientStatus::Pending)->exists()) {
            ProcessCampaignChunkJob::dispatch($campaign->id)
                ->delay(now()->addSeconds($campaign->chunk_delay_seconds));
        } else {
            $campaign->update([
                'status' => CampaignStatus::Completed,
                'completed_at' => now(),
            ]);
        }
    }

    public function cancel(SmsCampaign $campaign): SmsCampaign
    {
        if (! in_array($campaign->status, [CampaignStatus::Pending, CampaignStatus::Processing], true)) {
            throw new BusinessException('Bu kampanya iptal edilemez.');
        }

        $campaign->update([
            'status' => CampaignStatus::Cancelled,
            'completed_at' => now(),
        ]);

        return $campaign->fresh();
    }
}
