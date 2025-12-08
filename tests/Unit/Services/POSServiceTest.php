<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Sale;
use App\Services\POSService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class POSServiceTest extends TestCase
{
    use RefreshDatabase;

    protected POSService $service;
    protected Branch $branch;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(POSService::class);

        $this->branch = Branch::create([
            'name' => 'Test Branch',
            'code' => 'TB001',
        ]);

        $this->product = Product::create([
            'name' => 'Test Product',
            'code' => 'PRD001',
            'sku' => 'SKU001',
            'type' => 'stock',
            'default_price' => 100,
            'standard_cost' => 50,
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_can_calculate_cart_total(): void
    {
        $items = [
            [
                'product_id' => $this->product->id,
                'quantity' => 2,
                'price' => 100,
            ],
        ];

        $total = $this->service->calculateCartTotal($items);

        $this->assertEquals(200, $total);
    }

    public function test_can_apply_discount_to_cart(): void
    {
        $subtotal = 1000;
        $discount = 10; // 10%

        $finalTotal = $this->service->applyDiscount($subtotal, $discount);

        $this->assertEquals(900, $finalTotal);
    }

    public function test_can_create_sale(): void
    {
        $data = [
            'customer_id' => null,
            'total' => 100,
            'paid' => 100,
            'payment_method' => 'cash',
            'branch_id' => $this->branch->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'price' => 100,
                ],
            ],
        ];

        $sale = $this->service->createSale($data);

        $this->assertInstanceOf(Sale::class, $sale);
        $this->assertEquals(100, $sale->total);
    }

    public function test_validates_payment_amount(): void
    {
        $total = 100;
        $paid = 100;

        $isValid = $this->service->validatePayment($total, $paid);

        $this->assertTrue($isValid);
    }

    public function test_detects_insufficient_payment(): void
    {
        $total = 100;
        $paid = 50;

        $isValid = $this->service->validatePayment($total, $paid);

        $this->assertFalse($isValid);
    }
}
