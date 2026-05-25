<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\ReminderStatus;
use App\Models\Reminder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Reminder */
class ReminderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'customer_uuid' => $this->customer?->uuid,
            'customer_name' => $this->customer?->name,
            'message' => $this->message,
            'status' => $this->status instanceof ReminderStatus
                ? $this->status->value
                : (string) $this->status,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'server_version' => $this->server_version,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
