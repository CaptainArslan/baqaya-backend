<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\TransactionDirection;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'shop_id',
        'customer_id',
        'type',
        'amount',
        'direction',
        'payment_method',
        'reference_no',
        'description',
        'transaction_date',
        'balance_after',
        'created_by',
        'client_created_at',
        'client_updated_at',
        'server_version',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'direction' => TransactionDirection::class,
            'payment_method' => PaymentMethod::class,
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'transaction_date' => 'date',
            'client_created_at' => 'datetime',
            'client_updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'server_version' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $transaction): void {
            if (empty($transaction->uuid)) {
                $transaction->uuid = (string) Str::uuid();
            }
        });

        static::updating(function (self $transaction): void {
            $transaction->server_version++;
        });
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
}
