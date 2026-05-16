<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateCustomerStatementPdf;
use App\Models\CustomerStatementPdf;
use App\Models\Shop;
use App\Services\CustomerService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StatementController extends Controller
{
    public function __construct(
        private readonly CustomerService $customerService,
    ) {}

    public function generate(Request $request, string $customerUuid): JsonResponse
    {
        if (RateLimiter::tooManyAttempts('stmt:'.$request->user()->id, 10)) {
            return ApiResponse::error('Too many statement requests.', 'RATE_LIMITED', 429);
        }
        RateLimiter::hit('stmt:'.$request->user()->id, 300);

        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $validated = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        $customer = $this->customerService->findByUuid($shop, $customerUuid);

        $statement = CustomerStatementPdf::query()->create([
            'shop_id' => $shop->id,
            'customer_id' => $customer->id,
            'file_path' => '',
            'from_date' => $validated['from_date'] ?? null,
            'to_date' => $validated['to_date'] ?? null,
            'generated_at' => now(),
            'status' => 'pending',
            'requested_by' => $request->user()->id,
        ]);

        GenerateCustomerStatementPdf::dispatch($statement->id);

        return ApiResponse::success([
            'uuid' => $statement->uuid,
            'status' => $statement->status,
        ], 'Statement generation queued.', 202);
    }

    public function download(Request $request, string $uuid): StreamedResponse|JsonResponse
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $statement = CustomerStatementPdf::query()
            ->where('shop_id', $shop->id)
            ->where('uuid', $uuid)
            ->firstOrFail();

        if ($statement->status !== 'completed' || $statement->file_path === '') {
            return ApiResponse::error('Statement not ready.', 'NOT_READY', 409);
        }

        return Storage::disk('local')->download($statement->file_path, $statement->uuid.'.pdf');
    }
}
