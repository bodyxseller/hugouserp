# Module Boundary Testing Guide

**Purpose:** Ensure module independence and proper dependency management  
**Target Audience:** Developers, QA Engineers  
**Last Updated:** 2025-12-11

---

## Overview

Module boundary tests verify that:
1. **Independent modules** function without product/inventory dependencies
2. **Product-based modules** properly depend on the inventory module
3. **Module isolation** is maintained (no unintended cross-module coupling)
4. **Branch isolation** works correctly

---

## Test Categories

### 1. Independence Tests

**Goal:** Verify independent modules work without inventory/products

#### Test: HRM Module Independence
```php
/** @test */
public function hrm_module_works_without_inventory_module()
{
    // Given: Inventory module is disabled
    $this->disableModule('inventory');
    
    // When: Accessing HRM features
    $response = $this->actingAs($this->hrmUser)
        ->get(route('app.hrm.employees.index'));
    
    // Then: HRM should work normally
    $response->assertOk();
    $response->assertSeeLivewire('hrm.employees.index');
}

/** @test */
public function can_create_employee_without_inventory_module()
{
    $this->disableModule('inventory');
    
    $employeeData = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'position' => 'Developer',
        'salary' => 5000,
    ];
    
    Livewire::test(EmployeeForm::class)
        ->set('name', $employeeData['name'])
        ->set('email', $employeeData['email'])
        ->call('save');
    
    $this->assertDatabaseHas('hr_employees', [
        'name' => 'John Doe',
    ]);
}
```

#### Test: Accounting Module Independence
```php
/** @test */
public function accounting_module_works_without_inventory()
{
    $this->disableModule('inventory');
    
    $response = $this->actingAs($this->accountant)
        ->get(route('app.accounting.index'));
    
    $response->assertOk();
}

/** @test */
public function can_create_journal_entry_without_products()
{
    $this->disableModule('inventory');
    
    $journalEntry = JournalEntry::factory()->create();
    
    $this->assertDatabaseHas('journal_entries', [
        'id' => $journalEntry->id,
    ]);
}
```

#### Test: Rental Module Independence
```php
/** @test */
public function rental_module_works_independently()
{
    $this->disableModule('inventory');
    
    // Create rental unit
    $unit = RentalUnit::factory()->create();
    
    // Create contract
    $contract = RentalContract::factory()->create([
        'unit_id' => $unit->id,
    ]);
    
    $this->assertDatabaseHas('rental_contracts', [
        'id' => $contract->id,
    ]);
}
```

### 2. Dependency Tests

**Goal:** Verify product-based modules require inventory

#### Test: Manufacturing Module Dependencies
```php
/** @test */
public function manufacturing_requires_inventory_module()
{
    $this->disableModule('inventory');
    
    $response = $this->actingAs($this->productionManager)
        ->get(route('app.manufacturing.boms.index'));
    
    // Should show warning or redirect
    $response->assertStatus(403) // Or redirect to dashboard
        ->assertSee('Inventory module required');
}

/** @test */
public function cannot_create_bom_without_products()
{
    $this->disableModule('inventory');
    
    Livewire::test(BomForm::class)
        ->set('product_id', 999) // Non-existent product
        ->call('save')
        ->assertHasErrors('product_id');
}

/** @test */
public function bom_references_single_products_table()
{
    $product = Product::factory()->create();
    $bom = BillOfMaterial::factory()->create([
        'product_id' => $product->id,
    ]);
    
    // Verify FK relationship
    $this->assertEquals($product->id, $bom->product->id);
    $this->assertInstanceOf(Product::class, $bom->product);
}
```

#### Test: POS Module Dependencies
```php
/** @test */
public function pos_requires_products_to_function()
{
    Product::query()->delete();
    
    $response = $this->actingAs($this->cashier)
        ->get(route('pos.terminal'));
    
    $response->assertOk()
        ->assertSee('No products available');
}

/** @test */
public function pos_sale_items_reference_products_table()
{
    $product = Product::factory()->create();
    $sale = Sale::factory()->create();
    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
    ]);
    
    $this->assertEquals($product->id, $saleItem->product->id);
}
```

### 3. Cross-Module Isolation Tests

**Goal:** Ensure modules don't have unintended dependencies

#### Test: No Circular Dependencies
```php
/** @test */
public function accounting_does_not_depend_on_hrm()
{
    $this->disableModule('hrm');
    
    $account = Account::factory()->create();
    $journalEntry = JournalEntry::factory()->create();
    
    $this->assertDatabaseHas('accounts', ['id' => $account->id]);
    $this->assertDatabaseHas('journal_entries', ['id' => $journalEntry->id]);
}

/** @test */
public function rental_does_not_depend_on_manufacturing()
{
    $this->disableModule('manufacturing');
    
    $unit = RentalUnit::factory()->create();
    $contract = RentalContract::factory()->create([
        'unit_id' => $unit->id,
    ]);
    
    $this->assertDatabaseHas('rental_contracts', [
        'id' => $contract->id,
    ]);
}
```

### 4. Branch Isolation Tests

**Goal:** Verify data isolation between branches

#### Test: Branch Data Isolation
```php
/** @test */
public function sales_are_isolated_per_branch()
{
    $branchA = Branch::factory()->create(['name' => 'Branch A']);
    $branchB = Branch::factory()->create(['name' => 'Branch B']);
    
    $saleA = Sale::factory()->create(['branch_id' => $branchA->id]);
    $saleB = Sale::factory()->create(['branch_id' => $branchB->id]);
    
    // Switch to Branch A context
    $this->actingAs($this->user->assignToBranch($branchA));
    session(['current_branch_id' => $branchA->id]);
    
    $sales = Sale::all();
    
    $this->assertCount(1, $sales);
    $this->assertEquals($saleA->id, $sales->first()->id);
    $this->assertFalse($sales->contains($saleB));
}

/** @test */
public function products_can_be_shared_across_branches_when_configured()
{
    $branchA = Branch::factory()->create();
    $branchB = Branch::factory()->create();
    
    $product = Product::factory()->create([
        'branch_id' => $branchA->id,
    ]);
    
    // Enable global product sharing
    SystemSetting::set('inventory.share_products_globally', true);
    
    $this->actingAs($this->user->assignToBranch($branchB));
    session(['current_branch_id' => $branchB->id]);
    
    $this->assertTrue(
        Product::where('id', $product->id)->exists()
    );
}
```

### 5. Schema Consistency Tests

**Goal:** Ensure no duplicate or shadow tables

#### Test: Single Products Table
```php
/** @test */
public function only_one_products_table_exists()
{
    $tables = DB::select('SHOW TABLES');
    
    $productTables = array_filter($tables, function ($table) {
        $tableName = array_values((array) $table)[0];
        return str_contains($tableName, 'product') 
            && !str_contains($tableName, 'module_product')
            && !str_contains($tableName, 'product_');
    });
    
    $this->assertCount(1, $productTables);
}

/** @test */
public function all_product_foreign_keys_reference_products_table()
{
    $foreignKeys = DB::select("
        SELECT 
            TABLE_NAME, 
            COLUMN_NAME, 
            REFERENCED_TABLE_NAME
        FROM 
            information_schema.KEY_COLUMN_USAGE
        WHERE 
            REFERENCED_TABLE_NAME IS NOT NULL
            AND COLUMN_NAME = 'product_id'
    ");
    
    foreach ($foreignKeys as $fk) {
        $this->assertEquals('products', $fk->REFERENCED_TABLE_NAME,
            "Table {$fk->TABLE_NAME} references wrong table for product_id"
        );
    }
}
```

---

## Test Helper Methods

### Helper: Disable Module

```php
// tests/TestCase.php

protected function disableModule(string $moduleKey): void
{
    Module::where('key', $moduleKey)->update(['is_active' => false]);
    
    // Clear cached module settings
    Cache::forget("modules.{$moduleKey}.enabled");
}

protected function enableModule(string $moduleKey): void
{
    Module::where('key', $moduleKey)->update(['is_active' => true]);
    Cache::forget("modules.{$moduleKey}.enabled");
}
```

### Helper: Branch Context

```php
protected function switchToBranch(Branch $branch): void
{
    session(['current_branch_id' => $branch->id]);
}

protected function actingAsUserInBranch(User $user, Branch $branch)
{
    $user->branches()->syncWithoutDetaching([$branch->id]);
    $this->switchToBranch($branch);
    return $this->actingAs($user);
}
```

---

## Running the Tests

### Full Test Suite
```bash
php artisan test
```

### Module Boundary Tests Only
```bash
php artisan test --testsuite=ModuleBoundaries
```

### Specific Module Tests
```bash
php artisan test --filter=HrmModuleTest
php artisan test --filter=AccountingModuleTest
php artisan test --filter=ManufacturingDependencyTest
```

---

## Test Configuration

### Create Test Suite

Add to `phpunit.xml`:

```xml
<testsuites>
    <testsuite name="ModuleBoundaries">
        <directory suffix="Test.php">./tests/Feature/ModuleBoundaries</directory>
    </testsuite>
</testsuites>
```

### Test Directory Structure

```
tests/
├── Feature/
│   ├── ModuleBoundaries/
│   │   ├── IndependentModulesTest.php
│   │   ├── ProductBasedModulesTest.php
│   │   ├── BranchIsolationTest.php
│   │   ├── SchemaConsistencyTest.php
│   │   └── CrossModuleTest.php
│   └── ...
└── Unit/
    └── ...
```

---

## Continuous Integration

### GitHub Actions Workflow

```yaml
name: Module Boundary Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          
      - name: Install Dependencies
        run: composer install --no-interaction
        
      - name: Run Module Boundary Tests
        run: php artisan test --testsuite=ModuleBoundaries
```

---

## Test Checklist

When adding a new module, ensure these tests pass:

### For Independent Modules
- [ ] Module loads without inventory/products enabled
- [ ] CRUD operations work independently
- [ ] No foreign keys to products table
- [ ] No references to product-based module tables
- [ ] Branch isolation works correctly

### For Product-Based Modules
- [ ] Module checks for inventory module availability
- [ ] All foreign keys point to `products` table (not shadow tables)
- [ ] Graceful degradation when products unavailable
- [ ] Branch-scoped product access
- [ ] No circular dependencies with other product-based modules

---

## Common Pitfalls

### ❌ Creating Shadow Product Tables
```php
// DON'T DO THIS
Schema::create('manufacturing_products', function (Blueprint $table) {
    // ...
});
```

### ✅ Use the Core Products Table
```php
// DO THIS
Schema::create('bom_items', function (Blueprint $table) {
    $table->foreignId('product_id')->constrained('products');
});
```

### ❌ Hard-Coding Module Dependencies
```php
// DON'T DO THIS
public function index()
{
    $employees = HREmployee::with('department')->get();
    // Assumes department module exists
}
```

### ✅ Check Module Availability
```php
// DO THIS
public function index()
{
    $employees = HREmployee::query();
    
    if (module_enabled('departments')) {
        $employees->with('department');
    }
    
    return $employees->get();
}
```

---

## Future Enhancements

1. **Automated Dependency Analysis**
   - Tool to scan codebase for unintended cross-module dependencies
   
2. **Performance Testing**
   - Benchmark module operations under isolation
   
3. **Integration Test Scenarios**
   - Real-world workflows spanning multiple modules
   
4. **Visual Dependency Graph**
   - Generate module dependency diagram from test results

---

## Resources

- **Architecture Guide:** `/docs/ARCHITECTURE.md`
- **Deep Verification Report:** `/DEEP_VERIFICATION_REPORT.md`
- **Contributing Guidelines:** `/CONTRIBUTING.md`

---

**Maintained By:** Development Team  
**Questions?** Open an issue or contact the team
