<?php

declare(strict_types=1);

namespace Tests\Feature\Manufacturing;

use App\Models\BillOfMaterial;
use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BomCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Branch $branch;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create(['name' => 'Test Branch', 'code' => 'TB001']);
        $this->user = User::factory()->create(['branch_id' => $this->branch->id]);
        $this->product = Product::create([
            'name' => 'Finished Product',
            'code' => 'FIN001',
            'type' => 'stock',
            'default_price' => 1000,
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_can_create_bom(): void
    {
        $bom = BillOfMaterial::create([
            'product_id' => $this->product->id,
            'name' => 'BOM for Product',
            'quantity' => 1,
            'status' => 'active',
            'branch_id' => $this->branch->id,
        ]);

        $this->assertDatabaseHas('bill_of_materials', ['name' => 'BOM for Product']);
    }

    public function test_can_read_bom(): void
    {
        $bom = BillOfMaterial::create([
            'product_id' => $this->product->id,
            'name' => 'BOM for Product',
            'quantity' => 1,
            'status' => 'active',
            'branch_id' => $this->branch->id,
        ]);

        $found = BillOfMaterial::find($bom->id);
        $this->assertNotNull($found);
    }

    public function test_can_update_bom(): void
    {
        $bom = BillOfMaterial::create([
            'product_id' => $this->product->id,
            'name' => 'BOM for Product',
            'quantity' => 1,
            'status' => 'active',
            'branch_id' => $this->branch->id,
        ]);

        $bom->update(['status' => 'archived']);
        $this->assertDatabaseHas('bill_of_materials', ['id' => $bom->id, 'status' => 'archived']);
    }

    public function test_can_delete_bom(): void
    {
        $bom = BillOfMaterial::create([
            'product_id' => $this->product->id,
            'name' => 'BOM for Product',
            'quantity' => 1,
            'status' => 'active',
            'branch_id' => $this->branch->id,
        ]);

        $bom->delete();
        $this->assertSoftDeleted('bill_of_materials', ['id' => $bom->id]);
    }
}
