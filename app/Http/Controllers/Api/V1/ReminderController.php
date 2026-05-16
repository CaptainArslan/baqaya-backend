<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\ReminderStatus;
use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppReminder;
use App\Models\Reminder;
use App\Models\Shop;
use App\Services\CustomerService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReminderController extends Controller
{
    public function __construct(
        private readonly CustomerService $customerService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $reminders = Reminder::query()
            ->with(['customer', 'logs'])
            ->where('shop_id', $shop->id)
            ->orderByDesc('created_at')
            ->paginate((int) $request->query('per_page', 20));

        return ApiResponse::success(
            $reminders->items(),
            meta: [
                'current_page' => $reminders->currentPage(),
                'total' => $reminders->total(),
            ],
        );
    }

    public function send(Request $request): JsonResponse
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $validated = $request->validate([
            'customer_uuid' => ['required', 'string'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $customer = $this->customerService->findByUuid($shop, $validated['customer_uuid']);

        $reminder = Reminder::query()->create([
            'shop_id' => $shop->id,
            'customer_id' => $customer->id,
            'created_by' => $request->user()->id,
            'message' => $validated['message'],
            'status' => ReminderStatus::Queued,
        ]);

        SendWhatsAppReminder::dispatch($reminder->id);

        return ApiResponse::success([
            'uuid' => $reminder->uuid,
            'status' => $reminder->status instanceof ReminderStatus
                ? $reminder->status->value
                : (string) $reminder->status,
        ], 'Reminder queued.', 202);
    }
}
