<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\TransactionResource;
use App\Models\Shop;
use App\Models\UserDevice;
use App\Services\SyncService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class SyncController extends Controller
{
    public function __construct(
        private readonly SyncService $syncService,
    ) {}

    public function push(Request $request): JsonResponse
    {
        if (RateLimiter::tooManyAttempts('sync:'.$request->user()->id, 60)) {
            return ApiResponse::error('Too many sync requests.', 'RATE_LIMITED', 429);
        }
        RateLimiter::hit('sync:'.$request->user()->id, 60);

        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $validated = $request->validate([
            'request_id' => ['required', 'string', 'max:100'],
            'device_id' => ['required', 'string', 'max:255'],
            'operations' => ['required', 'array'],
            'operations.*.operation_id' => ['required', 'string', 'max:100'],
            'operations.*.type' => ['required', 'string', 'max:80'],
            'operations.*.payload' => ['required', 'array'],
        ]);

        $device = UserDevice::query()
            ->where('user_id', $request->user()->id)
            ->where('device_id', $validated['device_id'])
            ->firstOrFail();

        $result = $this->syncService->push($shop, $request->user(), $device, $validated, $request);

        return ApiResponse::success($result);
    }

    public function pull(Request $request): JsonResponse
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $validated = $request->validate([
            'device_id' => ['required', 'string', 'max:255'],
            'since_version' => ['nullable', 'integer', 'min:0'],
        ]);

        $device = UserDevice::query()
            ->where('user_id', $request->user()->id)
            ->where('device_id', $validated['device_id'])
            ->firstOrFail();

        $data = $this->syncService->pull(
            $shop,
            $request->user(),
            $device,
            (int) ($validated['since_version'] ?? 0),
        );

        return ApiResponse::success([
            'cursor' => $data['cursor'],
            'customers' => CustomerResource::collection($data['customers']),
            'transactions' => TransactionResource::collection($data['transactions']),
            'payments' => PaymentResource::collection($data['payments']),
            'reminders' => $data['reminders'],
        ]);
    }
}
