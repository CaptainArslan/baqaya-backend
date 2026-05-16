<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Payment */
class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'customer_uuid' => $this->customer?->uuid,
            'amount' => $this->amount,
            'payment_method' => $this->payment_method->value,
            'status' => $this->status?->value ?? 'active',
            'reference_no' => $this->reference_no,
            'description' => $this->description,
            'notes' => $this->notes,
            'payment_date' => $this->payment_date?->format('Y-m-d'),
            'balance_after' => $this->balance_after,
            'server_version' => $this->server_version,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
