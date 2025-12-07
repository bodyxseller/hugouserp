<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates stock quantities for inventory operations
 *
 * Features:
 * - Ensures positive or zero quantities
 * - Maximum value validation
 * - Decimal precision control
 * - Context-aware messages
 */
class ValidStockQuantity implements ValidationRule
{
    /**
     * Floating point comparison tolerance
     */
    private const FLOAT_EPSILON = 0.000001;

    public function __construct(
        private float $maxQuantity = 999999.99,
        private int $decimalPlaces = 2,
        private bool $allowZero = false,
        private ?string $context = null
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check if numeric
        if (! is_numeric($value)) {
            $fail(__('The :attribute must be a number'));

            return;
        }

        $quantity = (float) $value;

        // Check if negative
        if ($quantity < 0) {
            $fail(__('The :attribute cannot be negative'));

            return;
        }

        // Check if zero when not allowed
        if (! $this->allowZero && abs($quantity) < self::FLOAT_EPSILON) {
            $fail(__('The :attribute must be greater than zero'));

            return;
        }

        // Check maximum
        if ($quantity > $this->maxQuantity) {
            $fail(__('The :attribute cannot exceed :max', ['max' => number_format($this->maxQuantity, $this->decimalPlaces)]));

            return;
        }

        // Check decimal places
        $parts = explode('.', (string) $value);
        if (isset($parts[1]) && strlen($parts[1]) > $this->decimalPlaces) {
            $fail(__('The :attribute cannot have more than :places decimal places', ['places' => $this->decimalPlaces]));

            return;
        }

        // Context-specific validations
        if ($this->context === 'stock_out' && $quantity === 0.0) {
            $fail(__('Stock out quantity must be greater than zero'));

            return;
        }
    }
}
