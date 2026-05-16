<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\WhatsappStatus;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Customer */
class CustomerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'phone' => $this->phone,
            'whatsapp_status' => $this->whatsapp_status instanceof WhatsappStatus
                ? $this->whatsapp_status->value
                : $this->whatsapp_status,
            'address' => $this->address,
            'opening_balance' => $this->opening_balance,
            'current_balance' => $this->current_balance,
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            'server_version' => $this->server_version,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
