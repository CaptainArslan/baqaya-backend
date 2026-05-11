<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CustomerStatementPdf extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'shop_id',
        'customer_id',
        'file_path',
        'from_date',
        'to_date',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
            'generated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $pdf): void {
            if (empty($pdf->uuid)) {
                $pdf->uuid = (string) Str::uuid();
            }
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
}
