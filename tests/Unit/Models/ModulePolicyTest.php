<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Branch;
use App\Models\Module;
use App\Models\ModulePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModulePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected Module $module;

    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->module = Module::create([
            'key' => 'test_module',
            'name' => 'Test Module',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'name' => 'Test Branch',
            'code' => 'TB001',
        ]);
    }

    public function test_can_create_module_policy(): void
    {
        $policy = ModulePolicy::create([
            'module_id' => $this->module->id,
            'branch_id' => $this->branch->id,
            'policy_key' => 'test_policy',
            'policy_name' => 'Test Policy',
            'policy_description' => 'A test policy',
            'policy_rules' => ['rule1' => 'value1'],
            'scope' => 'branch',
            'is_active' => true,
            'priority' => 100,
        ]);

        $this->assertDatabaseHas('module_policies', [
            'module_id' => $this->module->id,
            'policy_key' => 'test_policy',
        ]);
    }

    public function test_policy_belongs_to_module(): void
    {
        $policy = ModulePolicy::create([
            'module_id' => $this->module->id,
            'policy_key' => 'test_policy',
            'policy_name' => 'Test Policy',
            'policy_rules' => [],
        ]);

        $this->assertInstanceOf(Module::class, $policy->module);
        $this->assertEquals($this->module->id, $policy->module->id);
    }

    public function test_policy_can_be_scoped_to_active(): void
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

        $activePolicies = ModulePolicy::active()->get();
        $this->assertCount(1, $activePolicies);
        $this->assertEquals('active_policy', $activePolicies->first()->policy_key);
    }

    public function test_policy_evaluation_returns_true_for_matching_rules(): void
    {
        $policy = ModulePolicy::create([
            'module_id' => $this->module->id,
            'policy_key' => 'test_policy',
            'policy_name' => 'Test Policy',
            'policy_rules' => ['check_stock' => true, 'allow_negative' => false],
            'is_active' => true,
        ]);

        $context = ['check_stock' => true, 'allow_negative' => false];
        $this->assertTrue($policy->evaluate($context));
    }

    public function test_policy_evaluation_returns_false_for_non_matching_rules(): void
    {
        $policy = ModulePolicy::create([
            'module_id' => $this->module->id,
            'policy_key' => 'test_policy',
            'policy_name' => 'Test Policy',
            'policy_rules' => ['check_stock' => true],
            'is_active' => true,
        ]);

        $context = ['check_stock' => false];
        $this->assertFalse($policy->evaluate($context));
    }

    public function test_inactive_policy_evaluation_returns_false(): void
    {
        $policy = ModulePolicy::create([
            'module_id' => $this->module->id,
            'policy_key' => 'test_policy',
            'policy_name' => 'Test Policy',
            'policy_rules' => [],
            'is_active' => false,
        ]);

        $this->assertFalse($policy->evaluate([]));
    }
}
