<?php

namespace App\Services\Sms;

use App\Enums\CampaignRecipientStatus;
use App\Enums\SmsMessageStatus;
use App\Models\SmsCampaignRecipient;
use App\Models\SmsMessage;
use App\Models\User;
use App\Repositories\Contracts\SmsMessageRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\WalletServiceInterface;
use App\Sms\DTOs\SmsSendResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SmsMessageResultHandler
{
    public function __construct(
        private readonly SmsMessageRepositoryInterface $smsMessageRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly WalletServiceInterface $walletService,
    ) {}

    public function handle(SmsMessage $smsMessage, SmsSendResult $result): void
    {
        DB::transaction(function () use ($smsMessage, $result): void {
            if ($result->success) {
                $this->smsMessageRepository->update($smsMessage, [
                    'status' => SmsMessageStatus::Sent->value,
                    'provider_message_id' => $result->messageId,
                    'sent_at' => now(),
                    'error_message' => null,
                ]);

                $this->updateCampaignRecipient($smsMessage, CampaignRecipientStatus::Sent);

                return;
            }

            $errorMessage = $result->errorMessage ?? 'SMS gönderilemedi.';

            $this->smsMessageRepository->update($smsMessage, [
                'status' => SmsMessageStatus::Failed->value,
                'error_message' => $errorMessage,
            ]);

            $this->updateCampaignRecipient($smsMessage, CampaignRecipientStatus::Failed, $errorMessage);

            /** @var User|null $user */
            $user = $this->userRepository->findById($smsMessage->user_id);
            if ($user !== null) {
                $this->walletService->refund(
                    $user,
                    (float) $smsMessage->segments,
                    "Başarısız SMS iadesi ({$smsMessage->segments} segment): {$smsMessage->recipient}",
                    $smsMessage,
                );
            }

            Log::channel('daily')->warning('SMS gönderimi başarısız', [
                'sms_message_id' => $smsMessage->id,
                'error' => $errorMessage,
            ]);
        });
    }

    private function updateCampaignRecipient(
        SmsMessage $smsMessage,
        CampaignRecipientStatus $status,
        ?string $errorMessage = null,
    ): void {
        SmsCampaignRecipient::query()
            ->where('sms_message_id', $smsMessage->id)
            ->update([
                'status' => $status->value,
                'error_message' => $errorMessage,
                'updated_at' => now(),
            ]);
    }
}
