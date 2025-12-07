<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Rules\ValidDiscountPercentage;
use App\Rules\ValidPhoneNumber;
use App\Rules\ValidStockQuantity;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ValidationRulesTest extends TestCase
{
    /** @test */
    public function valid_phone_number_accepts_correct_formats(): void
    {
        $rule = new ValidPhoneNumber;

        $validNumbers = [
            '+1234567890',
            '(123) 456-7890',
            '123-456-7890',
            '123.456.7890',
            '1234567890',
            '+966 50 123 4567',
        ];

        foreach ($validNumbers as $number) {
            $validator = Validator::make(
                ['phone' => $number],
                ['phone' => [$rule]]
            );

            $this->assertFalse(
                $validator->fails(),
                "Phone number '{$number}' should be valid but was rejected"
            );
        }
    }

    /** @test */
    public function valid_phone_number_rejects_incorrect_formats(): void
    {
        $rule = new ValidPhoneNumber;

        $invalidNumbers = [
            'abc123',
            '12',
            '12345',
            '+abc',
            '123@456',
        ];

        foreach ($invalidNumbers as $number) {
            $validator = Validator::make(
                ['phone' => $number],
                ['phone' => [$rule]]
            );

            $this->assertTrue(
                $validator->fails(),
                "Phone number '{$number}' should be invalid but was accepted"
            );
        }
    }

    /** @test */
    public function valid_stock_quantity_accepts_positive_numbers(): void
    {
        $rule = new ValidStockQuantity;

        $validQuantities = [10, 10.5, 100.25, 1.00];

        foreach ($validQuantities as $qty) {
            $validator = Validator::make(
                ['qty' => $qty],
                ['qty' => [$rule]]
            );

            $this->assertFalse(
                $validator->fails(),
                "Quantity '{$qty}' should be valid but was rejected"
            );
        }
    }

    /** @test */
    public function valid_stock_quantity_rejects_negative_numbers(): void
    {
        $rule = new ValidStockQuantity;

        $validator = Validator::make(
            ['qty' => -10],
            ['qty' => [$rule]]
        );

        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function valid_stock_quantity_rejects_zero_when_not_allowed(): void
    {
        $rule = new ValidStockQuantity(allowZero: false);

        $validator = Validator::make(
            ['qty' => 0],
            ['qty' => [$rule]]
        );

        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function valid_stock_quantity_accepts_zero_when_allowed(): void
    {
        $rule = new ValidStockQuantity(allowZero: true);

        $validator = Validator::make(
            ['qty' => 0],
            ['qty' => [$rule]]
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function valid_stock_quantity_enforces_maximum(): void
    {
        $rule = new ValidStockQuantity(maxQuantity: 100);

        $validator = Validator::make(
            ['qty' => 150],
            ['qty' => [$rule]]
        );

        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function valid_stock_quantity_enforces_decimal_places(): void
    {
        $rule = new ValidStockQuantity(decimalPlaces: 2);

        $validator = Validator::make(
            ['qty' => 10.123],
            ['qty' => [$rule]]
        );

        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function valid_discount_percentage_accepts_valid_percentages(): void
    {
        $rule = new ValidDiscountPercentage;

        $validPercentages = [0, 10, 25.5, 50, 99.99, 100];

        foreach ($validPercentages as $discount) {
            $validator = Validator::make(
                ['discount' => $discount],
                ['discount' => [$rule]]
            );

            $this->assertFalse(
                $validator->fails(),
                "Discount '{$discount}' should be valid but was rejected"
            );
        }
    }

    /** @test */
    public function valid_discount_percentage_rejects_negative_numbers(): void
    {
        $rule = new ValidDiscountPercentage;

        $validator = Validator::make(
            ['discount' => -10],
            ['discount' => [$rule]]
        );

        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function valid_discount_percentage_rejects_over_maximum(): void
    {
        $rule = new ValidDiscountPercentage(maxDiscount: 50);

        $validator = Validator::make(
            ['discount' => 60],
            ['discount' => [$rule]]
        );

        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function valid_discount_percentage_accepts_null(): void
    {
        $rule = new ValidDiscountPercentage;

        $validator = Validator::make(
            ['discount' => null],
            ['discount' => ['nullable', $rule]]
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function valid_discount_percentage_enforces_decimal_places(): void
    {
        $rule = new ValidDiscountPercentage(decimalPlaces: 1);

        $validator = Validator::make(
            ['discount' => 10.123],
            ['discount' => [$rule]]
        );

        $this->assertTrue($validator->fails());
    }
}
