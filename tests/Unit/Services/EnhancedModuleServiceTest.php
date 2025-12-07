<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Branch;
use App\Models\BranchModule;
use App\Models\Module;
use App\Models\ModulePolicy;
use App\Services\ModuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EnhancedModuleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ModuleService $service;

    protected Branch $branch;

    protected Module $module;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ModuleService::class);

        $this->branch = Branch::create([
            'name' => 'Test Branch',
            'code' => 'TB001',
        ]);

        $this->module = Module::create([
            'key' => 'test_module',
            'name' => 'Test Module',
            'module_type' => 'data',
            'is_active' => true,
            'supports_reporting' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    public function test_get_modules_by_type_returns_correct_modules(): void
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

        $dataModules = $this->service->getModulesByType('data');
        $this->assertCount(2, $dataModules); // test_module + data_module

        $functionalModules = $this->service->getModulesByType('functional');
        $this->assertCount(1, $functionalModules);
    }

    public function test_get_modules_by_type_filters_by_branch_enabled_modules(): void
    {
        $enabledModule = Module::create([
            'key' => 'enabled_module',
            'name' => 'Enabled Module',
            'module_type' => 'data',
            'is_active' => true,
        ]);

        $disabledModule = Module::create([
            'key' => 'disabled_module',
            'name' => 'Disabled Module',
            'module_type' => 'data',
            'is_active' => true,
        ]);

        BranchModule::create([
            'branch_id' => $this->branch->id,
            'module_id' => $enabledModule->id,
            'module_key' => $enabledModule->key,
            'enabled' => true,
        ]);

        BranchModule::create([
            'branch_id' => $this->branch->id,
            'module_id' => $disabledModule->id,
            'module_key' => $disabledModule->key,
            'enabled' => false,
        ]);

        $modules = $this->service->getModulesByType('data', $this->branch->id);
        $moduleKeys = collect($modules)->pluck('key')->toArray();

        $this->assertContains('enabled_module', $moduleKeys);
        $this->assertNotContains('disabled_module', $moduleKeys);
    }

    public function test_get_active_policies_returns_module_policies(): void
    {
        ModulePolicy::create([
            'module_id' => $this->module->id,
            'branch_id' => null,
            'policy_key' => 'global_policy',
            'policy_name' => 'Global Policy',
            'policy_rules' => ['rule' => 'value'],
            'scope' => 'global',
            'is_active' => true,
        ]);

        ModulePolicy::create([
            'module_id' => $this->module->id,
            'branch_id' => $this->branch->id,
            'policy_key' => 'branch_policy',
            'policy_name' => 'Branch Policy',
            'policy_rules' => ['rule' => 'value'],
            'scope' => 'branch',
            'is_active' => true,
        ]);

        $policies = $this->service->getActivePolicies($this->module->id, $this->branch->id);
        $this->assertCount(2, $policies);

        $policyKeys = collect($policies)->pluck('key')->toArray();
        $this->assertContains('global_policy', $policyKeys);
        $this->assertContains('branch_policy', $policyKeys);
    }

    public function test_get_active_policies_excludes_inactive_policies(): void
    {
        ModulePolicy::create([
            'module_id' => $this->module->id,
            'policy_key' => 'active_policy',
            'policy_name' => 'Active Policy',
            'policy_rules' => [],
            'is_active' => true,
        ]);

        ModulePolicy::create([
            'module_id' => $this->module->id,
            'policy_key' => 'inactive_policy',
            'policy_name' => 'Inactive Policy',
            'policy_rules' => [],
            'is_active' => false,
        ]);

        $policies = $this->service->getActivePolicies($this->module->id);
        $this->assertCount(1, $policies);
        $this->assertEquals('active_policy', $policies[0]['key']);
    }
}
