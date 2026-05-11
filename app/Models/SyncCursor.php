<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncCursor extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'user_id',
        'device_id',
        'last_server_version',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'last_server_version' => 'integer',
            'last_synced_at' => 'datetime',
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
}
