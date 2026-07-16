<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\Sms\SendBulkSmsData;
use App\DTOs\Sms\SendSmsData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sms\SendBulkSmsRequest;
use App\Http\Requests\Sms\SendSmsRequest;
use App\Models\SmsMessage;
use App\Services\Contact\ContactService;
use App\Services\Contracts\SmsSendServiceInterface;
use App\Services\Contracts\UserSenderNumberServiceInterface;
use App\Services\Contracts\WalletServiceInterface;
use App\Services\Sms\SmsTemplateService;
use App\Sms\Support\PhoneNormalizer;
use App\Sms\Support\SmsSegmentCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SmsSendController extends Controller
{
    public function __construct(
        private readonly SmsSendServiceInterface $smsSendService,
        private readonly WalletServiceInterface $walletService,
        private readonly UserSenderNumberServiceInterface $userSenderNumberService,
        private readonly ContactService $contactService,
        private readonly SmsTemplateService $smsTemplateService,
        private readonly SmsSegmentCalculator $segmentCalculator,
        private readonly PhoneNormalizer $phoneNormalizer,
    ) {}

    public function create(): View
    {
        $this->authorize('create', SmsMessage::class);

        $user = auth()->user();
        $senderNumbers = $this->userSenderNumberService->getActiveForUser($user);
        $defaultSender = $senderNumbers->firstWhere('is_default', true)?->sender_id
            ?? $senderNumbers->first()?->sender_id
            ?? $this->userSenderNumberService->resolveSenderId($user, null);

        return view('admin.sms.send', [
            'pageTitle' => 'SMS Gönder',
            'balance' => $this->walletService->getAvailableBalance($user),
            'defaultSenderId' => $defaultSender,
            'senderNumbers' => $senderNumbers,
            'hasAssignedSenders' => $senderNumbers->isNotEmpty(),
            'maxBatchSize' => (int) config('sms.batch_size', 1000),
            'contacts' => $this->contactService->getActiveForUser($user),
            'templates' => $this->smsTemplateService->activeForUser($user),
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $this->authorize('create', SmsMessage::class);

        $message = (string) $request->input('message', '');
        $recipient = (string) $request->input('recipient', '');

        $segments = $this->segmentCalculator->calculateSegments($message);
        $isUnicode = $this->segmentCalculator->requiresUnicodeEncoding($message);

        return response()->json([
            'chars' => mb_strlen($message),
            'segments' => $segments,
            'credits' => $segments,
            'encoding' => $isUnicode ? 'unicode' : 'gsm',
            'recipient_valid' => $recipient === '' || $this->phoneNormalizer->isValidTurkishMobile(
                $this->phoneNormalizer->normalize($recipient)
            ),
        ]);
    }

    public function store(SendSmsRequest $request): RedirectResponse|JsonResponse
    {
        $message = $this->smsSendService->send(
            auth()->user(),
            SendSmsData::fromArray($request->validated()),
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'SMS kuyruğa alındı.',
                'data' => [
                    'id' => $message->id,
                    'recipient' => $message->recipient,
                    'segments' => $message->segments,
                    'sender_id' => $message->sender_id,
                    'status' => $message->status->value,
                ],
            ]);
        }

        return redirect()
            ->route('admin.sms.history.index')
            ->with('success', 'SMS başarıyla kuyruğa alındı.');
    }

    public function storeBulk(SendBulkSmsRequest $request): RedirectResponse|JsonResponse
    {
        $messages = $this->smsSendService->sendBulk(
            auth()->user(),
            SendBulkSmsData::fromArray($request->validated()),
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "{$messages->count()} SMS kuyruğa alındı.",
                'count' => $messages->count(),
            ]);
        }

        return redirect()
            ->route('admin.sms.history.index')
            ->with('success', "{$messages->count()} SMS başarıyla kuyruğa alındı.");
    }
}
