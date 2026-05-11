<?php

declare(strict_types=1);

namespace App\Enums;

enum WhatsappStatus: string
{
    case Unknown = 'unknown';
    case Valid = 'valid';
    case Invalid = 'invalid';
}
