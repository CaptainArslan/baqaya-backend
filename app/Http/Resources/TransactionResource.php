<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Transaction */
class TransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'customer_uuid' => $this->customer?->uuid,
            'type' => $this->type->value,
            'status' => $this->status?->value ?? 'active',
            'amount' => $this->amount,
            'direction' => $this->direction?->value,
            'reference_no' => $this->reference_no,
            'description' => $this->description,
            'notes' => $this->notes,
            'category' => $this->category,
            'transaction_date' => $this->transaction_date?->format('Y-m-d'),
            'balance_after' => $this->balance_after,
            'server_version' => $this->server_version,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
