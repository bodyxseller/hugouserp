<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates discount percentages
 *
 * Features:
 * - Range validation (0-100%)
 * - Decimal precision control
 * - Maximum discount limit
 */
class ValidDiscountPercentage implements ValidationRule
{
    public function __construct(
        private float $maxDiscount = 100.0,
        private int $decimalPlaces = 2
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        // Check if numeric
        if (! is_numeric($value)) {
            $fail(__('The :attribute must be a number'));

            return;
        }

        $discount = (float) $value;

        // Check if negative
        if ($discount < 0) {
            $fail(__('The :attribute cannot be negative'));

            return;
        }

        // Check maximum
        if ($discount > $this->maxDiscount) {
            $fail(__('The :attribute cannot exceed :max%', ['max' => number_format($this->maxDiscount, 0)]));

            return;
        }

        // Check decimal places
        $parts = explode('.', (string) $value);
        if (isset($parts[1]) && strlen($parts[1]) > $this->decimalPlaces) {
            $fail(__('The :attribute cannot have more than :places decimal places', ['places' => $this->decimalPlaces]));

            return;
        }
    }
}
