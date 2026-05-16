<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SyncRequest extends Model
{
    use HasPublicId;

    protected $fillable = [
        'uuid',
        'shop_id',
        'user_id',
        'device_id',
        'request_id',
        'operations_count',
        'response_payload',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'response_payload' => 'array',
            'operations_count' => 'integer',
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

    public function operations(): HasMany
    {
        return $this->hasMany(SyncOperation::class);
    }
}
