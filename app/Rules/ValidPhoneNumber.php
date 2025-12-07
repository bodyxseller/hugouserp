<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates phone numbers with international format support
 *
 * Accepts formats:
 * - +1234567890
 * - (123) 456-7890
 * - 123-456-7890
 * - 123.456.7890
 * - 1234567890
 */
class ValidPhoneNumber implements ValidationRule
{
    public function __construct(
        private bool $requireInternationalFormat = false
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        // Remove common separators
        $cleanValue = preg_replace('/[\s\-\(\)\.]/', '', $value);

        // Check if it starts with + for international format
        if ($this->requireInternationalFormat && ! str_starts_with($cleanValue, '+')) {
            $fail(__('The :attribute must be in international format starting with +'));

            return;
        }

        // Remove + sign for digit check
        $digitsOnly = ltrim($cleanValue, '+');

        // Check if only digits remain
        if (! ctype_digit($digitsOnly)) {
            $fail(__('The :attribute must contain only numbers and valid separators'));

            return;
        }

        // Check length (international format: 7-15 digits)
        $length = strlen($digitsOnly);
        if ($length < 7 || $length > 15) {
            $fail(__('The :attribute must be between 7 and 15 digits'));

            return;
        }
    }
}
