<?php

declare(strict_types=1);

namespace Tests\Feature\Hrm;

use App\Models\Branch;
use App\Models\HrEmployee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create(['name' => 'Test Branch', 'code' => 'TB001']);
        $this->user = User::factory()->create(['branch_id' => $this->branch->id]);
    }

    public function test_can_create_employee(): void
    {
        $employee = HrEmployee::create([
            'employee_code' => 'EMP001',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'hire_date' => now(),
            'position' => 'Developer',
            'salary' => 5000,
            'salary_type' => 'monthly',
            'employment_type' => 'full_time',
            'status' => 'active',
            'branch_id' => $this->branch->id,
        ]);

        $this->assertDatabaseHas('hr_employees', ['employee_code' => 'EMP001']);
    }

    public function test_can_read_employee(): void
    {
        $employee = HrEmployee::create([
            'employee_code' => 'EMP001',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'hire_date' => now(),
            'position' => 'Developer',
            'salary' => 5000,
            'salary_type' => 'monthly',
            'employment_type' => 'full_time',
            'status' => 'active',
            'branch_id' => $this->branch->id,
        ]);

        $found = HrEmployee::find($employee->id);
        $this->assertNotNull($found);
    }

    public function test_can_update_employee(): void
    {
        $employee = HrEmployee::create([
            'employee_code' => 'EMP001',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'hire_date' => now(),
            'position' => 'Developer',
            'salary' => 5000,
            'salary_type' => 'monthly',
            'employment_type' => 'full_time',
            'status' => 'active',
            'branch_id' => $this->branch->id,
        ]);

        $employee->update(['position' => 'Senior Developer']);
        $this->assertDatabaseHas('hr_employees', ['id' => $employee->id, 'position' => 'Senior Developer']);
    }

    public function test_can_delete_employee(): void
    {
        $employee = HrEmployee::create([
            'employee_code' => 'EMP001',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'hire_date' => now(),
            'position' => 'Developer',
            'salary' => 5000,
            'salary_type' => 'monthly',
            'employment_type' => 'full_time',
            'status' => 'active',
            'branch_id' => $this->branch->id,
        ]);

        $employee->delete();
        $this->assertSoftDeleted('hr_employees', ['id' => $employee->id]);
    }
}
