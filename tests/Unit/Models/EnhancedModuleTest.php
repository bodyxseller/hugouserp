<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Module;
use App\Models\ModuleNavigation;
use App\Models\ModuleOperation;
use App\Models\ModulePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnhancedModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_has_type_classification(): void
    {
        $dataModule = Module::create([
            'key' => 'inventory',
            'name' => 'Inventory',
            'module_type' => 'data',
            'is_active' => true,
        ]);

        $functionalModule = Module::create([
            'key' => 'pos',
            'name' => 'POS',
            'module_type' => 'functional',
            'is_active' => true,
        ]);

        $this->assertEquals('data', $dataModule->module_type);
        $this->assertEquals('functional', $functionalModule->module_type);
        $this->assertTrue($dataModule->isDataOriented());
        $this->assertTrue($functionalModule->isFunctional());
    }

    public function test_module_has_policies_relationship(): void
    {
        $module = Module::create([
            'key' => 'test_module',
            'name' => 'Test Module',
            'is_active' => true,
        ]);

        $policy = ModulePolicy::create([
            'module_id' => $module->id,
            'policy_key' => 'test_policy',
            'policy_name' => 'Test Policy',
            'policy_rules' => [],
        ]);

        $this->assertCount(1, $module->policies);
        $this->assertEquals($policy->id, $module->policies->first()->id);
    }

    public function test_module_has_operations_relationship(): void
    {
        $module = Module::create([
            'key' => 'test_module',
            'name' => 'Test Module',
            'is_active' => true,
        ]);

        $operation = ModuleOperation::create([
            'module_id' => $module->id,
            'operation_key' => 'create',
            'operation_name' => 'Create',
            'operation_type' => 'create',
        ]);

        $this->assertCount(1, $module->operations);
        $this->assertEquals($operation->id, $module->operations->first()->id);
    }

    public function test_module_has_navigation_relationship(): void
    {
        $module = Module::create([
            'key' => 'test_module',
            'name' => 'Test Module',
            'is_active' => true,
        ]);

        $nav = ModuleNavigation::create([
            'module_id' => $module->id,
            'nav_key' => 'main_nav',
            'nav_label' => 'Main Navigation',
        ]);

        $this->assertCount(1, $module->navigation);
        $this->assertEquals($nav->id, $module->navigation->first()->id);
    }

    public function test_module_supports_reporting_flag(): void
    {
        $module = Module::create([
            'key' => 'test_module',
            'name' => 'Test Module',
            'is_active' => true,
            'supports_reporting' => true,
            'supports_custom_fields' => false,
        ]);

        $this->assertTrue($module->supports_reporting);
        $this->assertFalse($module->supports_custom_fields);
    }

    public function test_module_can_store_operation_config(): void
    {
        $module = Module::create([
            'key' => 'test_module',
            'name' => 'Test Module',
            'is_active' => true,
            'operation_config' => [
                'max_batch_size' => 1000,
                'timeout' => 300,
            ],
        ]);

        $this->assertEquals(1000, $module->getOperationConfig('max_batch_size'));
        $this->assertEquals(300, $module->getOperationConfig('timeout'));
        $this->assertNull($module->getOperationConfig('nonexistent'));
    }

    public function test_module_can_store_integration_hooks(): void
    {
        $module = Module::create([
            'key' => 'test_module',
            'name' => 'Test Module',
            'is_active' => true,
            'integration_hooks' => [
                'woocommerce' => true,
                'shopify' => false,
            ],
        ]);

        $this->assertTrue($module->getIntegrationHook('woocommerce'));
        $this->assertFalse($module->getIntegrationHook('shopify'));
    }

    public function test_data_oriented_scope_filters_data_modules(): void
    {
        Module::create([
            'key' => 'data_module',
            'name' => 'Data Module',
            'module_type' => 'data',
            'is_active' => true,
        ]);

        Module::create([
            'key' => 'functional_module',
            'name' => 'Functional Module',
            'module_type' => 'functional',
            'is_active' => true,
        ]);

        $dataModules = Module::dataOriented()->get();
        $this->assertCount(1, $dataModules);
        $this->assertEquals('data_module', $dataModules->first()->key);
    }

    public function test_functional_scope_filters_functional_modules(): void
    {
        Module::create([
            'key' => 'data_module',
            'name' => 'Data Module',
            'module_type' => 'data',
            'is_active' => true,
        ]);

        Module::create([
            'key' => 'functional_module',
            'name' => 'Functional Module',
            'module_type' => 'functional',
            'is_active' => true,
        ]);

        $functionalModules = Module::functional()->get();
        $this->assertCount(1, $functionalModules);
        $this->assertEquals('functional_module', $functionalModules->first()->key);
    }
}
