<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Module;
use App\Models\ModuleOperation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleOperationTest extends TestCase
{
    use RefreshDatabase;

    protected Module $module;

    protected function setUp(): void
    {
        parent::setUp();

        $this->module = Module::create([
            'key' => 'test_module',
            'name' => 'Test Module',
            'is_active' => true,
        ]);
    }

    public function test_can_create_module_operation(): void
    {
        $operation = ModuleOperation::create([
            'module_id' => $this->module->id,
            'operation_key' => 'create',
            'operation_name' => 'Create',
            'operation_type' => 'create',
            'operation_config' => ['key' => 'value'],
            'required_permissions' => ['test_module.create'],
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('module_operations', [
            'module_id' => $this->module->id,
            'operation_key' => 'create',
        ]);
    }

    public function test_operation_belongs_to_module(): void
    {
        $operation = ModuleOperation::create([
            'module_id' => $this->module->id,
            'operation_key' => 'read',
            'operation_name' => 'Read',
            'operation_type' => 'read',
        ]);

        $this->assertInstanceOf(Module::class, $operation->module);
        $this->assertEquals($this->module->id, $operation->module->id);
    }

    public function test_can_get_operation_config_value(): void
    {
        $operation = ModuleOperation::create([
            'module_id' => $this->module->id,
            'operation_key' => 'create',
            'operation_name' => 'Create',
            'operation_type' => 'create',
            'operation_config' => ['max_items' => 100, 'timeout' => 30],
        ]);

        $this->assertEquals(100, $operation->getConfig('max_items'));
        $this->assertEquals(30, $operation->getConfig('timeout'));
        $this->assertEquals('default', $operation->getConfig('nonexistent', 'default'));
    }

    public function test_active_scope_filters_active_operations(): void
    {
        ModuleOperation::create([
            'module_id' => $this->module->id,
            'operation_key' => 'active_op',
            'operation_name' => 'Active Operation',
            'operation_type' => 'create',
            'is_active' => true,
        ]);

        ModuleOperation::create([
            'module_id' => $this->module->id,
            'operation_key' => 'inactive_op',
            'operation_name' => 'Inactive Operation',
            'operation_type' => 'create',
            'is_active' => false,
        ]);

        $activeOps = ModuleOperation::active()->get();
        $this->assertCount(1, $activeOps);
        $this->assertEquals('active_op', $activeOps->first()->operation_key);
    }

    public function test_ordered_scope_orders_by_sort_order(): void
    {
        ModuleOperation::create([
            'module_id' => $this->module->id,
            'operation_key' => 'second',
            'operation_name' => 'Second Operation',
            'operation_type' => 'create',
            'sort_order' => 20,
        ]);

        ModuleOperation::create([
            'module_id' => $this->module->id,
            'operation_key' => 'first',
            'operation_name' => 'First Operation',
            'operation_type' => 'create',
            'sort_order' => 10,
        ]);

        $operations = ModuleOperation::ordered()->get();
        $this->assertEquals('first', $operations->first()->operation_key);
        $this->assertEquals('second', $operations->last()->operation_key);
    }
}
