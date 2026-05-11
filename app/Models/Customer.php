<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WhatsappStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'shop_id',
        'name',
        'phone',
        'whatsapp_status',
        'address',
        'photo_path',
        'opening_balance',
        'current_balance',
        'notes',
        'is_active',
        'client_created_at',
        'client_updated_at',
        'server_version',
    ];

    protected function casts(): array
    {
        return [
            'whatsapp_status' => WhatsappStatus::class,
            'opening_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'is_active' => 'boolean',
            'client_created_at' => 'datetime',
            'client_updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'server_version' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $customer): void {
            if (empty($customer->uuid)) {
                $customer->uuid = (string) Str::uuid();
            }
        });

        static::updating(function (self $customer): void {
            $customer->server_version++;
        });
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function statementPdfs(): HasMany
    {
        return $this->hasMany(CustomerStatementPdf::class);
    }
}
