<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AuditActionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'shop_id',
        'user_id',
        'device_id',
        'action_type',
        'entity_type',
        'entity_uuid',
        'sync_origin',
        'ip_address',
        'old_values',
        'new_values',
    ];

    protected function casts(): array
    {
        return [
            'action_type' => AuditActionType::class,
            'sync_origin' => 'boolean',
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(UserDevice::class, 'device_id');
    }
}
