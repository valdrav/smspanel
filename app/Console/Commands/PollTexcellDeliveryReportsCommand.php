<?php

namespace App\Console\Commands;

use App\Enums\SmsMessageStatus;
use App\Enums\SmsProviderDriver;
use App\Models\SmsMessage;
use App\Models\SmsProvider;
use App\Services\Sms\SmsDeliveryReportService;
use App\Sms\Providers\TexcellEimsSmsProvider;
use App\Sms\SmsProviderFactory;
use Illuminate\Console\Command;

/**
 * Texcell /getreport ile henüz teslim edilmemiş mesajların DLR durumunu çeker.
 */
class PollTexcellDeliveryReportsCommand extends Command
{
    protected $signature = 'sms:texcell-poll-reports
                            {--hours=72 : Kaç saat geriye bakılsın}
                            {--limit=500 : Maksimum mesaj sayısı}';

    protected $description = 'Texcell EIMS teslimat raporlarını (getreport) günceller';

    public function handle(
        SmsProviderFactory $factory,
        SmsDeliveryReportService $deliveryReportService,
    ): int {
        $hours = max(1, (int) $this->option('hours'));
        $limit = max(1, min(5000, (int) $this->option('limit')));

        $providerCodes = SmsProvider::query()
            ->where('driver', SmsProviderDriver::Texcell->value)
            ->where('is_active', true)
            ->pluck('code')
            ->all();

        if ($providerCodes === []) {
            $providerCodes = ['texcell'];
        }

        $messages = SmsMessage::query()
            ->whereIn('provider', $providerCodes)
            ->where('status', SmsMessageStatus::Sent->value)
            ->whereNotNull('provider_message_id')
            ->where('sent_at', '>=', now()->subHours($hours))
            ->orderByDesc('sent_at')
            ->limit($limit)
            ->get(['id', 'provider', 'provider_message_id']);

        if ($messages->isEmpty()) {
            $this->info('Güncellenecek Texcell mesajı yok.');

            return self::SUCCESS;
        }

        $applied = 0;

        foreach ($messages->groupBy('provider') as $code => $group) {
            $provider = $factory->resolveByCode((string) $code);

            if (! $provider instanceof TexcellEimsSmsProvider) {
                $this->warn("Sağlayıcı Texcell değil, atlandı: {$code}");

                continue;
            }

            $ids = $group->pluck('provider_message_id')->filter()->map(fn ($id) => (string) $id)->values()->all();
            $reports = $provider->getReports($ids);

            foreach ($group as $message) {
                $mid = (string) $message->provider_message_id;
                $result = $reports[$mid] ?? null;

                if ($result === null) {
                    continue;
                }

                $status = (string) ($result->status ?? '');

                if ($status === 'pending') {
                    continue;
                }

                $ok = $deliveryReportService->apply((string) $code, [
                    'message_id' => $mid,
                    'success' => $result->success && $status !== 'failed',
                    'delivered' => $status === 'delivered',
                    'cause' => $result->errorMessage,
                ]);

                if ($ok && $status !== 'sent') {
                    $applied++;
                }
            }
        }

        $this->info("Texcell DLR güncellendi: {$applied} mesaj.");

        return self::SUCCESS;
    }
}
