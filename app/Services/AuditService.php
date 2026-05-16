<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditActionType;
use App\Models\AuditLog;
use App\Models\Shop;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;

class AuditService
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function log(
        AuditActionType $actionType,
        string $entityType,
        ?string $entityUuid = null,
        ?Shop $shop = null,
        ?User $user = null,
        ?UserDevice $device = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        bool $syncOrigin = false,
        ?Request $request = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'shop_id' => $shop?->id,
            'user_id' => $user?->id,
            'device_id' => $device?->id,
            'action_type' => $actionType,
            'entity_type' => $entityType,
            'entity_uuid' => $entityUuid,
            'sync_origin' => $syncOrigin,
            'ip_address' => $request?->ip(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }
}
