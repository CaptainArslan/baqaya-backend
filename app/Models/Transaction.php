<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FinancialRecordStatus;
use App\Enums\PaymentMethod;
use App\Enums\TransactionDirection;
use App\Enums\TransactionType;
use App\Support\HasPublicId;
use App\Support\IncrementsServerVersion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory;
    use HasPublicId;
    use IncrementsServerVersion;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'shop_id',
        'customer_id',
        'type',
        'status',
        'amount',
        'direction',
        'payment_method',
        'reference_no',
        'description',
        'notes',
        'category',
        'attachment_path',
        'transaction_date',
        'balance_after',
        'reversal_of_transaction_id',
        'corrected_by_transaction_id',
        'correction_reason',
        'corrected_at',
        'corrected_by',
        'device_id',
        'sync_operation_id',
        'created_by',
        'client_created_at',
        'client_updated_at',
        'server_version',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'status' => FinancialRecordStatus::class,
            'direction' => TransactionDirection::class,
            'payment_method' => PaymentMethod::class,
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'transaction_date' => 'date',
            'corrected_at' => 'datetime',
            'client_created_at' => 'datetime',
            'client_updated_at' => 'datetime',
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

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_transaction_id');
    }

    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'corrected_by_transaction_id');
    }
}
