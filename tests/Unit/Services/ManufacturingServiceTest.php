<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\BillOfMaterial;
use App\Models\Branch;
use App\Models\Product;
use App\Services\ManufacturingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManufacturingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ManufacturingService $service;
    protected Branch $branch;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ManufacturingService::class);

        $this->branch = Branch::create([
            'name' => 'Test Branch',
            'code' => 'TB001',
        ]);

        $this->product = Product::create([
            'name' => 'Finished Product',
            'code' => 'FIN001',
            'type' => 'stock',
            'default_price' => 1000,
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_can_create_bill_of_materials(): void
    {
        $data = [
            'product_id' => $this->product->id,
            'name' => 'BOM for Product',
            'quantity' => 1,
            'status' => 'active',
            'branch_id' => $this->branch->id,
        ];

        $bom = $this->service->createBOM($data);

        $this->assertInstanceOf(BillOfMaterial::class, $bom);
    }

    public function test_can_calculate_bom_total_cost(): void
    {
        $materials = [
            ['quantity' => 2, 'unit_cost' => 100],
            ['quantity' => 3, 'unit_cost' => 50],
        ];

        $totalCost = $this->service->calculateBOMCost($materials);

        $this->assertEquals(350, $totalCost);
    }

    public function test_validates_bom_structure(): void
    {
        $data = [
            'product_id' => $this->product->id,
            'items' => [
                ['product_id' => 1, 'quantity' => 2],
            ],
        ];

        $isValid = $this->service->validateBOMStructure($data);

        $this->assertTrue($isValid);
    }
}
