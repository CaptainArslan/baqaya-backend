<?php

declare(strict_types=1);

namespace App\Rules;

use App\Support\PakistanPhone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates canonical Pakistani mobile: 923XXXXXXXXX only.
 */
final class PakistanMobilePhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || ! PakistanPhone::isValid($value)) {
            $fail('The :attribute must be a valid Pakistani mobile number (923XXXXXXXXX).');
        }
    }
}
