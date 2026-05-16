<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\TransactionDirection;
use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Shop;
use App\Models\Transaction;
use App\Services\CustomerService;
use App\Services\TransactionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactionService,
        private readonly CustomerService $customerService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $query = Transaction::query()
            ->with('customer')
            ->where('shop_id', $shop->id)
            ->orderByDesc('transaction_date');

        if ($request->filled('customer_uuid')) {
            $customer = $this->customerService->findByUuid($shop, $request->query('customer_uuid'));
            $query->where('customer_id', $customer->id);
        }

        $transactions = $query->paginate((int) $request->query('per_page', 20));

        return ApiResponse::success(
            TransactionResource::collection($transactions),
            meta: ['total' => $transactions->total()],
        );
    }

    public function store(Request $request): JsonResponse
    {
        if (RateLimiter::tooManyAttempts('tx:'.$request->user()->id, 120)) {
            return ApiResponse::error('Too many requests.', 'RATE_LIMITED', 429);
        }
        RateLimiter::hit('tx:'.$request->user()->id, 60);

        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $validated = $request->validate([
            'customer_uuid' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['required', 'date'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $customer = $this->customerService->findByUuid($shop, $validated['customer_uuid']);

        $transaction = $this->transactionService->createCredit(
            $shop,
            $customer,
            $request->user(),
            $validated,
            null,
            null,
            false,
            $request,
        );

        return ApiResponse::success(new TransactionResource($transaction->load('customer')), 'Transaction created.', 201);
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $transaction = Transaction::query()
            ->with('customer')
            ->where('shop_id', $shop->id)
            ->where('uuid', $uuid)
            ->firstOrFail();

        return ApiResponse::success(new TransactionResource($transaction));
    }

    public function adjustment(Request $request): JsonResponse
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $validated = $request->validate([
            'customer_uuid' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'direction' => ['required', Rule::enum(TransactionDirection::class)],
            'transaction_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $customer = $this->customerService->findByUuid($shop, $validated['customer_uuid']);

        $transaction = $this->transactionService->createAdjustment(
            $shop,
            $customer,
            $request->user(),
            $validated,
            null,
            null,
            false,
            $request,
        );

        return ApiResponse::success(new TransactionResource($transaction->load('customer')), 'Adjustment created.', 201);
    }

    public function correct(Request $request, string $uuid): JsonResponse
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['nullable', 'date'],
            'correction_reason' => ['required', 'string', 'max:500'],
            'notes' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'reference_no' => ['nullable', 'string', 'max:100'],
        ]);

        $original = Transaction::query()->where('shop_id', $shop->id)->where('uuid', $uuid)->firstOrFail();

        $corrected = $this->transactionService->correct(
            $shop,
            $original,
            $request->user(),
            $validated,
            null,
            null,
            false,
            $request,
        );

        return ApiResponse::success(new TransactionResource($corrected->load('customer')));
    }

    public function reverse(Request $request, string $uuid): JsonResponse
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $validated = $request->validate([
            'correction_reason' => ['required', 'string', 'max:500'],
        ]);

        $original = Transaction::query()->where('shop_id', $shop->id)->where('uuid', $uuid)->firstOrFail();

        $reversal = $this->transactionService->reverse(
            $shop,
            $original,
            $request->user(),
            $validated['correction_reason'],
            null,
            null,
            false,
            $request,
        );

        return ApiResponse::success(new TransactionResource($reversal->load('customer')));
    }

    public function updateMetadata(Request $request, string $uuid): JsonResponse
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'attachment_path' => ['nullable', 'string'],
        ]);

        $transaction = Transaction::query()->where('shop_id', $shop->id)->where('uuid', $uuid)->firstOrFail();

        $updated = $this->transactionService->updateMetadata(
            $shop,
            $transaction,
            $request->user(),
            $validated,
            null,
            $request,
        );

        return ApiResponse::success(new TransactionResource($updated->load('customer')));
    }
}
