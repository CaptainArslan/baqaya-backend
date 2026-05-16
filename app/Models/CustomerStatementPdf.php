<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerStatementPdf extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $fillable = [
        'uuid',
        'shop_id',
        'customer_id',
        'file_path',
        'from_date',
        'to_date',
        'generated_at',
        'status',
        'requested_by',
    ];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
            'generated_at' => 'datetime',
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
}
