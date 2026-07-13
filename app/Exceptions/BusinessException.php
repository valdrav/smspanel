<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * İş kuralı ihlalleri için temel exception sınıfı.
 */
class BusinessException extends Exception
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message = 'İşlem gerçekleştirilemedi.',
        protected int $statusCode = 422,
        protected array $context = [],
    ) {
        parent::__construct($message, $statusCode);
    }

    /**
     * HTTP yanıtını oluşturur.
     */
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'context' => $this->context,
            ], $this->statusCode);
        }

        return back()
            ->withInput()
            ->with('error', $this->getMessage());
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }
}
