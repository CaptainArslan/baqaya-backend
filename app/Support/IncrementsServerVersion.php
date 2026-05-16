<?php

declare(strict_types=1);

namespace App\Support;

trait IncrementsServerVersion
{
    protected static function bootIncrementsServerVersion(): void
    {
        static::updating(function (self $model): void {
            $model->server_version = ($model->server_version ?? 0) + 1;
        });
    }
}
