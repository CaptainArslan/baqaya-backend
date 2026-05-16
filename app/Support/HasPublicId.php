<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

trait HasPublicId
{
    protected static function bootHasPublicId(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::ulid();
            }
        });
    }
}
