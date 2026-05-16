<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncOperation extends Model
{
    protected $fillable = [
        'sync_request_id',
        'operation_id',
        'operation_type',
        'payload',
        'status',
        'result',
        'error_code',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'result' => 'array',
        ];
    }

    public function syncRequest(): BelongsTo
    {
        return $this->belongsTo(SyncRequest::class);
    }
}
