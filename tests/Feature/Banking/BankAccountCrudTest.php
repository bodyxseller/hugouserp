<?php

declare(strict_types=1);

namespace Tests\Feature\Banking;

use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankAccountCrudTest extends TestCase
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

    public function test_can_create_bank_account(): void
    {
        $account = BankAccount::create([
            'account_name' => 'Main Account',
            'account_number' => '1234567890',
            'bank_name' => 'Test Bank',
            'currency' => 'EGP',
            'balance' => 10000,
            'branch_id' => $this->branch->id,
        ]);

        $this->assertDatabaseHas('bank_accounts', ['account_name' => 'Main Account']);
    }

    public function test_can_read_bank_account(): void
    {
        $account = BankAccount::create([
            'account_name' => 'Main Account',
            'account_number' => '1234567890',
            'bank_name' => 'Test Bank',
            'currency' => 'EGP',
            'balance' => 10000,
            'branch_id' => $this->branch->id,
        ]);

        $found = BankAccount::find($account->id);
        $this->assertNotNull($found);
    }

    public function test_can_update_bank_account(): void
    {
        $account = BankAccount::create([
            'account_name' => 'Main Account',
            'account_number' => '1234567890',
            'bank_name' => 'Test Bank',
            'currency' => 'EGP',
            'balance' => 10000,
            'branch_id' => $this->branch->id,
        ]);

        $account->update(['balance' => 15000]);
        $this->assertDatabaseHas('bank_accounts', ['id' => $account->id, 'balance' => 15000]);
    }

    public function test_can_delete_bank_account(): void
    {
        $account = BankAccount::create([
            'account_name' => 'Main Account',
            'account_number' => '1234567890',
            'bank_name' => 'Test Bank',
            'currency' => 'EGP',
            'balance' => 10000,
            'branch_id' => $this->branch->id,
        ]);

        $account->delete();
        $this->assertSoftDeleted('bank_accounts', ['id' => $account->id]);
    }
}
