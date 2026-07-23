<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Sms\SmsDeliveryReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Texcell EIMS aktif DLR push endpoint'i.
 *
 * Doküman: PUT JSON body — type=report, array[[id, number, time, result, cause], ...]
 * Texcell panelinde push URL olarak bu adresi tanımlayın.
 */
class TexcellReportWebhookController extends Controller
{
    public function __construct(
        private readonly SmsDeliveryReportService $deliveryReportService,
    ) {}

    public function __invoke(Request $request, ?string $token = null): JsonResponse
    {
        $expected = trim((string) config('sms.texcell.webhook_token', ''));

        if ($expected !== '' && ! hash_equals($expected, (string) $token)) {
            Log::channel('daily')->warning('Texcell DLR: geçersiz webhook token', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['status' => -1, 'message' => 'unauthorized'], 401);
        }

        $payload = $request->json()->all();
        if ($payload === []) {
            $payload = $request->all();
        }

        $type = (string) ($payload['type'] ?? 'report');
        if ($type !== '' && strcasecmp($type, 'report') !== 0) {
            return response()->json(['status' => 0, 'message' => 'ignored']);
        }

        $rows = is_array($payload['array'] ?? null) ? $payload['array'] : [];
        $providerCode = (string) config('sms.texcell.provider_code', 'texcell');

        $result = $this->deliveryReportService->applyTexcellPushRows($providerCode, $rows);

        Log::channel('daily')->info('Texcell DLR push alındı', [
            'cnt' => $payload['cnt'] ?? count($rows),
            'applied' => $result['applied'],
            'skipped' => $result['skipped'],
        ]);

        return response()->json([
            'status' => 0,
            'applied' => $result['applied'],
            'skipped' => $result['skipped'],
        ]);
    }
}
