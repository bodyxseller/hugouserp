<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Branch;
use App\Models\RentalContract;
use App\Models\RentalProperty;
use App\Services\RentalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RentalService $service;
    protected Branch $branch;
    protected RentalProperty $property;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RentalService::class);

        $this->branch = Branch::create([
            'name' => 'Test Branch',
            'code' => 'TB001',
        ]);

        $this->property = RentalProperty::create([
            'name' => 'Test Property',
            'code' => 'PROP001',
            'type' => 'apartment',
            'status' => 'available',
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_can_check_property_availability(): void
    {
        $isAvailable = $this->service->isPropertyAvailable($this->property->id);

        $this->assertTrue($isAvailable);
    }

    public function test_can_calculate_rental_amount(): void
    {
        $monthlyRent = 5000;
        $months = 12;

        $totalAmount = $this->service->calculateRentalAmount($monthlyRent, $months);

        $this->assertEquals(60000, $totalAmount);
    }

    public function test_can_create_rental_contract(): void
    {
        $data = [
            'property_id' => $this->property->id,
            'tenant_name' => 'John Doe',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'monthly_rent' => 5000,
            'branch_id' => $this->branch->id,
        ];

        $contract = $this->service->createContract($data);

        $this->assertInstanceOf(RentalContract::class, $contract);
        $this->assertEquals(5000, $contract->monthly_rent);
    }

    public function test_validates_contract_dates(): void
    {
        $startDate = now();
        $endDate = now()->addYear();

        $isValid = $this->service->validateContractDates($startDate, $endDate);

        $this->assertTrue($isValid);
    }

    public function test_detects_invalid_contract_dates(): void
    {
        $startDate = now();
        $endDate = now()->subYear();

        $isValid = $this->service->validateContractDates($startDate, $endDate);

        $this->assertFalse($isValid);
    }
}
