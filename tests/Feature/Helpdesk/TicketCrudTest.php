<?php

declare(strict_types=1);

namespace Tests\Feature\Helpdesk;

use App\Models\Branch;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketCrudTest extends TestCase
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

    public function test_can_create_ticket(): void
    {
        $ticket = Ticket::create([
            'subject' => 'Test Issue',
            'description' => 'Test description',
            'status' => 'new',
            'branch_id' => $this->branch->id,
        ]);

        $this->assertDatabaseHas('tickets', ['subject' => 'Test Issue']);
    }

    public function test_can_read_ticket(): void
    {
        $ticket = Ticket::create([
            'subject' => 'Test Issue',
            'description' => 'Test description',
            'status' => 'new',
            'branch_id' => $this->branch->id,
        ]);

        $found = Ticket::find($ticket->id);
        $this->assertNotNull($found);
    }

    public function test_can_update_ticket(): void
    {
        $ticket = Ticket::create([
            'subject' => 'Test Issue',
            'description' => 'Test description',
            'status' => 'new',
            'branch_id' => $this->branch->id,
        ]);

        $ticket->update(['status' => 'resolved']);
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'resolved']);
    }

    public function test_can_delete_ticket(): void
    {
        $ticket = Ticket::create([
            'subject' => 'Test Issue',
            'description' => 'Test description',
            'status' => 'new',
            'branch_id' => $this->branch->id,
        ]);

        $ticket->delete();
        $this->assertSoftDeleted('tickets', ['id' => $ticket->id]);
    }
}
