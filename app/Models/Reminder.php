<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReminderStatus;
use App\Support\HasPublicId;
use App\Support\IncrementsServerVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reminder extends Model
{
    use HasPublicId;
    use IncrementsServerVersion;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'shop_id',
        'customer_id',
        'created_by',
        'message',
        'status',
        'scheduled_at',
        'sent_at',
        'server_version',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReminderStatus::class,
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'deleted_at' => 'datetime',
            'server_version' => 'integer',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ReminderLog::class);
    }
}
