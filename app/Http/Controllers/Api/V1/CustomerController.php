<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\LedgerEntryResource;
use App\Models\LedgerEntry;
use App\Models\Shop;
use App\Services\CustomerService;
use App\Support\ApiResponse;
use App\Support\PakistanPhone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerService $customerService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $customers = $this->customerService->list(
            $shop,
            $request->query('search'),
            (int) $request->query('per_page', 20),
        );

        return ApiResponse::success(
            CustomerResource::collection($customers),
            meta: [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
            ],
        );
    }

    public function store(Request $request): JsonResponse
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => PakistanPhone::optionalRules(),
            'address' => ['nullable', 'string'],
            'opening_balance' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        $customer = $this->customerService->create($shop, $request->user(), $validated);

        return ApiResponse::success(new CustomerResource($customer), 'Customer created.', 201);
    }

    public function bulkStore(Request $request): JsonResponse
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $validated = $request->validate([
            'customers' => ['required', 'array', 'min:1'],
            'customers.*.name' => ['required', 'string', 'max:255'],
            'customers.*.phone' => PakistanPhone::optionalRules(),
            'customers.*.address' => ['nullable', 'string'],
            'customers.*.opening_balance' => ['nullable', 'numeric'],
        ]);

        $customers = $this->customerService->bulkCreate($shop, $request->user(), $validated['customers']);

        return ApiResponse::success(CustomerResource::collection(collect($customers)), 'Customers imported.', 201);
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        return ApiResponse::success(
            new CustomerResource($this->customerService->findByUuid($shop, $uuid)),
        );
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => PakistanPhone::optionalRules(),
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $customer = $this->customerService->update(
            $shop,
            $this->customerService->findByUuid($shop, $uuid),
            $request->user(),
            $validated,
        );

        return ApiResponse::success(new CustomerResource($customer));
    }

    public function destroy(Request $request, string $uuid): JsonResponse
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $this->customerService->delete(
            $shop,
            $this->customerService->findByUuid($shop, $uuid),
            $request->user(),
        );

        return ApiResponse::success(message: 'Customer deleted.');
    }

    public function ledger(Request $request, string $uuid): JsonResponse
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');
        $customer = $this->customerService->findByUuid($shop, $uuid);

        $entries = LedgerEntry::query()
            ->with('customer')
            ->where('shop_id', $shop->id)
            ->where('customer_id', $customer->id)
            ->orderByDesc('created_at')
            ->paginate((int) $request->query('per_page', 50));

        return ApiResponse::success(
            LedgerEntryResource::collection($entries),
            meta: [
                'current_page' => $entries->currentPage(),
                'last_page' => $entries->lastPage(),
                'per_page' => $entries->perPage(),
                'total' => $entries->total(),
            ],
        );
    }

    public function match(Request $request): JsonResponse
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $validated = $request->validate([
            'phones' => ['required', 'array', 'min:1'],
            'phones.*' => PakistanPhone::requiredRules(),
        ]);

        $results = [];
        foreach ($validated['phones'] as $phone) {
            $customer = $this->customerService->findByPhone($shop, $phone);
            $results[] = [
                'phone' => $phone,
                'customer_uuid' => $customer?->uuid,
            ];
        }

        return ApiResponse::success($results);
    }
}
