<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\Shop;
use App\Services\CustomerService;
use App\Services\PaymentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly CustomerService $customerService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $query = Payment::query()
            ->with('customer')
            ->where('shop_id', $shop->id)
            ->orderByDesc('payment_date');

        if ($request->filled('customer_uuid')) {
            $customer = $this->customerService->findByUuid($shop, $request->query('customer_uuid'));
            $query->where('customer_id', $customer->id);
        }

        $payments = $query->paginate((int) $request->query('per_page', 20));

        return ApiResponse::success(
            PaymentResource::collection($payments),
            meta: [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        );
    }

    public function store(Request $request): JsonResponse
    {
        if (RateLimiter::tooManyAttempts('pay:'.$request->user()->id, 120)) {
            return ApiResponse::error('Too many requests.', 'RATE_LIMITED', 429);
        }
        RateLimiter::hit('pay:'.$request->user()->id, 60);

        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $validated = $request->validate([
            'customer_uuid' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $customer = $this->customerService->findByUuid($shop, $validated['customer_uuid']);

        $payment = $this->paymentService->create(
            $shop,
            $customer,
            $request->user(),
            $validated,
            null,
            null,
            false,
            $request,
        );

        return ApiResponse::success(new PaymentResource($payment->load('customer')), 'Payment recorded.', 201);
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $payment = Payment::query()
            ->with('customer')
            ->where('shop_id', $shop->id)
            ->where('uuid', $uuid)
            ->firstOrFail();

        return ApiResponse::success(new PaymentResource($payment));
    }

    public function correct(Request $request, string $uuid): JsonResponse
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['nullable', 'date'],
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'correction_reason' => ['required', 'string', 'max:500'],
            'notes' => ['nullable', 'string'],
        ]);

        $original = Payment::query()->where('shop_id', $shop->id)->where('uuid', $uuid)->firstOrFail();

        $corrected = $this->paymentService->correct(
            $shop,
            $original,
            $request->user(),
            $validated,
            null,
            null,
            false,
            $request,
        );

        return ApiResponse::success(new PaymentResource($corrected->load('customer')));
    }

    public function reverse(Request $request, string $uuid): JsonResponse
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $validated = $request->validate([
            'correction_reason' => ['required', 'string', 'max:500'],
        ]);

        $original = Payment::query()->where('shop_id', $shop->id)->where('uuid', $uuid)->firstOrFail();

        $reversal = $this->paymentService->reverse(
            $shop,
            $original,
            $request->user(),
            $validated['correction_reason'],
            null,
            null,
            false,
            $request,
        );

        return ApiResponse::success(new PaymentResource($reversal->load('customer')));
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

        $payment = Payment::query()->where('shop_id', $shop->id)->where('uuid', $uuid)->firstOrFail();

        $updated = $this->paymentService->updateMetadata(
            $shop,
            $payment,
            $request->user(),
            $validated,
            null,
            $request,
        );

        return ApiResponse::success(new PaymentResource($updated->load('customer')));
    }
}
