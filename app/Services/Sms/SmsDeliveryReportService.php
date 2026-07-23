<?php

namespace App\Services\Sms;

use App\Enums\CampaignRecipientStatus;
use App\Enums\SmsMessageStatus;
use App\Models\SmsCampaignRecipient;
use App\Models\SmsMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sağlayıcı teslimat raporlarını (DLR) sms_messages kaydına işler.
 *
 * Charge Rule "Send billing" olduğu için burada iade yapılmaz;
 * yalnızca durum (sent / delivered / failed) güncellenir.
 */
class SmsDeliveryReportService
{
    /**
     * @param  array{message_id: string, success: bool, delivered?: bool, cause?: string|null, number?: string|null}  $report
     */
    public function apply(string $providerCode, array $report): bool
    {
        $messageId = trim((string) ($report['message_id'] ?? ''));
        if ($messageId === '') {
            return false;
        }

        return DB::transaction(function () use ($providerCode, $messageId, $report): bool {
            /** @var SmsMessage|null $message */
            $message = SmsMessage::query()
                ->where('provider', $providerCode)
                ->where('provider_message_id', $messageId)
                ->lockForUpdate()
                ->first();

            if ($message === null) {
                Log::channel('daily')->info('DLR: mesaj bulunamadı', [
                    'provider' => $providerCode,
                    'provider_message_id' => $messageId,
                ]);

                return false;
            }

            if (in_array($message->status, [SmsMessageStatus::Delivered, SmsMessageStatus::Failed], true)) {
                return true;
            }

            $success = (bool) ($report['success'] ?? false);
            $delivered = (bool) ($report['delivered'] ?? $success);
            $cause = isset($report['cause']) ? trim((string) $report['cause']) : null;

            if ($success && $delivered) {
                $message->update([
                    'status' => SmsMessageStatus::Delivered->value,
                    'delivered_at' => now(),
                    'error_message' => null,
                ]);

                $this->updateCampaignRecipient($message, CampaignRecipientStatus::Sent);

                return true;
            }

            if ($success) {
                $message->update([
                    'status' => SmsMessageStatus::Sent->value,
                    'error_message' => null,
                ]);

                $this->updateCampaignRecipient($message, CampaignRecipientStatus::Sent);

                return true;
            }

            $errorMessage = $cause !== null && $cause !== '' ? $cause : 'Teslimat başarısız.';

            $message->update([
                'status' => SmsMessageStatus::Failed->value,
                'error_message' => $errorMessage,
            ]);

            $this->updateCampaignRecipient($message, CampaignRecipientStatus::Failed, $errorMessage);

            return true;
        });
    }

    /**
     * Texcell push formatı: array[[id, number, time, result, cause], ...]
     *
     * @param  list<mixed>  $rows
     * @return array{applied: int, skipped: int}
     */
    public function applyTexcellPushRows(string $providerCode, array $rows): array
    {
        $applied = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            if (! is_array($row) || count($row) < 4) {
                $skipped++;

                continue;
            }

            $resultCode = (int) $row[3];
            $ok = $this->apply($providerCode, [
                'message_id' => (string) $row[0],
                'number' => isset($row[1]) ? (string) $row[1] : null,
                'success' => $resultCode === 0,
                'delivered' => $resultCode === 0,
                'cause' => isset($row[4]) ? (string) $row[4] : null,
            ]);

            if ($ok) {
                $applied++;
            } else {
                $skipped++;
            }
        }

        return ['applied' => $applied, 'skipped' => $skipped];
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
