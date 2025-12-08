<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Branch;
use App\Models\HrEmployee;
use App\Services\HRMService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HRMServiceTest extends TestCase
{
    use RefreshDatabase;

    protected HRMService $service;
    protected Branch $branch;
    protected HrEmployee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(HRMService::class);

        $this->branch = Branch::create([
            'name' => 'Test Branch',
            'code' => 'TB001',
        ]);

        $this->employee = HrEmployee::create([
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
    }

    public function test_can_calculate_monthly_salary(): void
    {
        $salary = $this->service->calculateMonthlySalary($this->employee);

        $this->assertEquals(5000, $salary);
    }

    public function test_can_calculate_daily_salary_from_monthly(): void
    {
        $dailySalary = $this->service->calculateDailySalary($this->employee);

        $this->assertIsNumeric($dailySalary);
        $this->assertGreaterThan(0, $dailySalary);
    }

    public function test_can_validate_employee_status(): void
    {
        $isActive = $this->service->isEmployeeActive($this->employee);

        $this->assertTrue($isActive);
    }

    public function test_can_calculate_working_days_in_month(): void
    {
        $workingDays = $this->service->getWorkingDaysInMonth(now());

        $this->assertIsInt($workingDays);
        $this->assertGreaterThan(0, $workingDays);
        $this->assertLessThanOrEqual(31, $workingDays);
    }
}
