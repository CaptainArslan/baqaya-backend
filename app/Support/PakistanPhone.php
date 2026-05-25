<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Pakistani mobile numbers — canonical format only: 923XXXXXXXXX.
 */
final class PakistanPhone
{
    /** 12 digits: country code 92 + mobile 3XXXXXXXXX (no +, no leading 0). */
    public const CANONICAL_PATTERN = '/^923[0-9]{9}$/';

    public static function isValid(?string $phone): bool
    {
        return is_string($phone) && preg_match(self::CANONICAL_PATTERN, $phone) === 1;
    }

    /**
     * Required phone (auth OTP).
     *
     * @return list<string|\Illuminate\Validation\Rules\Regex>
     */
    public static function requiredRules(): array
    {
        return ['required', 'string', 'regex:'.self::CANONICAL_PATTERN];
    }

    /**
     * Optional phone (customers, shop). Empty/null skips regex.
     *
     * @return list<string|\Illuminate\Validation\Rules\Regex>
     */
    public static function optionalRules(): array
    {
        return ['nullable', 'string', 'regex:'.self::CANONICAL_PATTERN];
    }
}
