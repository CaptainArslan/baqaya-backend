<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\LedgerEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LedgerEntry */
class LedgerEntryResource extends JsonResource
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
            'entry_type' => $this->entry_type,
            'amount' => $this->amount,
            'balance_after' => $this->balance_after,
            'reference_date' => $this->created_at?->format('Y-m-d'),
            'created_at' => $this->created_at?->toIso8601String(),
            'notes' => null,
        ];
    }
}
