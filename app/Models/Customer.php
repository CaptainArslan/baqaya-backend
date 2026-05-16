<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WhatsappStatus;
use App\Support\HasPublicId;
use App\Support\IncrementsServerVersion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Shop-specific ledger contact (debtor/creditor). Not an app user and cannot authenticate.
 */
class Customer extends Model
{
    use HasFactory;
    use HasPublicId;
    use IncrementsServerVersion;
    use SoftDeletes;

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

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function statementPdfs(): HasMany
    {
        return $this->hasMany(CustomerStatementPdf::class);
    }
}
