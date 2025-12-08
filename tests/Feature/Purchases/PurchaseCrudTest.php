<?php

declare(strict_types=1);

namespace Tests\Feature\Purchases;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Branch $branch;
    protected Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create(['name' => 'Test Branch', 'code' => 'TB001']);
        $this->user = User::factory()->create(['branch_id' => $this->branch->id]);
        $this->supplier = Supplier::create(['name' => 'Test Supplier', 'branch_id' => $this->branch->id]);
    }

    public function test_can_create_purchase(): void
    {
        $purchase = Purchase::create([
            'purchase_number' => 'PO-001',
            'supplier_id' => $this->supplier->id,
            'total' => 1000,
            'status' => 'pending',
            'branch_id' => $this->branch->id,
        ]);

        $this->assertDatabaseHas('purchases', ['purchase_number' => 'PO-001']);
    }

    public function test_can_read_purchase(): void
    {
        $purchase = Purchase::create([
            'purchase_number' => 'PO-001',
            'supplier_id' => $this->supplier->id,
            'total' => 1000,
            'branch_id' => $this->branch->id,
        ]);

        $found = Purchase::find($purchase->id);
        $this->assertNotNull($found);
    }

    public function test_can_update_purchase(): void
    {
        $purchase = Purchase::create([
            'purchase_number' => 'PO-001',
            'supplier_id' => $this->supplier->id,
            'total' => 1000,
            'branch_id' => $this->branch->id,
        ]);

        $purchase->update(['status' => 'approved']);
        $this->assertDatabaseHas('purchases', ['id' => $purchase->id, 'status' => 'approved']);
    }

    public function test_can_delete_purchase(): void
    {
        $purchase = Purchase::create([
            'purchase_number' => 'PO-001',
            'supplier_id' => $this->supplier->id,
            'total' => 1000,
            'branch_id' => $this->branch->id,
        ]);

        $purchase->delete();
        $this->assertSoftDeleted('purchases', ['id' => $purchase->id]);
    }
}
