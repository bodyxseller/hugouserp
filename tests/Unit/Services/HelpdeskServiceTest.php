<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Branch;
use App\Models\Ticket;
use App\Services\HelpdeskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpdeskServiceTest extends TestCase
{
    use RefreshDatabase;

    protected HelpdeskService $service;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(HelpdeskService::class);

        $this->branch = Branch::create([
            'name' => 'Test Branch',
            'code' => 'TB001',
        ]);
    }

    public function test_can_create_ticket(): void
    {
        $data = [
            'subject' => 'Test Ticket',
            'description' => 'Test Description',
            'status' => 'new',
            'branch_id' => $this->branch->id,
        ];

        $ticket = $this->service->createTicket($data);

        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertEquals('Test Ticket', $ticket->subject);
    }

    public function test_can_assign_ticket(): void
    {
        $ticket = Ticket::create([
            'subject' => 'Test',
            'description' => 'Test',
            'status' => 'new',
            'branch_id' => $this->branch->id,
        ]);

        $assigned = $this->service->assignTicket($ticket->id, 1);

        $this->assertTrue($assigned);
    }

    public function test_can_update_ticket_status(): void
    {
        $ticket = Ticket::create([
            'subject' => 'Test',
            'description' => 'Test',
            'status' => 'new',
            'branch_id' => $this->branch->id,
        ]);

        $updated = $this->service->updateTicketStatus($ticket->id, 'in_progress');

        $this->assertTrue($updated);
    }

    public function test_can_add_ticket_reply(): void
    {
        $ticket = Ticket::create([
            'subject' => 'Test',
            'description' => 'Test',
            'status' => 'new',
            'branch_id' => $this->branch->id,
        ]);

        $reply = $this->service->addReply($ticket->id, [
            'message' => 'Test Reply',
            'user_id' => 1,
        ]);

        $this->assertNotNull($reply);
    }
}
