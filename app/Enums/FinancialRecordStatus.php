<?php

declare(strict_types=1);

namespace App\Enums;

enum FinancialRecordStatus: string
{
    case Active = 'active';
    case Corrected = 'corrected';
    case Reversed = 'reversed';
    case Voided = 'voided';
}
