<?php

declare(strict_types=1);

namespace App\Livewire\Helpdesk;

use App\Http\Requests\TicketReplyRequest;
use App\Models\Ticket;
use App\Models\User;
use App\Services\HelpdeskService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class TicketDetail extends Component
{
    use AuthorizesRequests;

    public Ticket $ticket;
    public string $replyMessage = '';
    public bool $isInternal = false;
    public ?int $assignToUser = null;

    protected HelpdeskService $helpdeskService;

    public function boot(HelpdeskService $helpdeskService): void
    {
        $this->helpdeskService = $helpdeskService;
    }

    public function mount(Ticket $ticket): void
    {
        $this->authorize('helpdesk.view');
        $this->ticket = $ticket->load(['customer', 'assignedAgent', 'category', 'priority', 'slaPolicy', 'replies.user']);
        $this->assignToUser = $ticket->assigned_to;
    }

    public function addReply(): void
    {
        $this->authorize('helpdesk.reply');

        $this->validate([
            'replyMessage' => 'required|string|min:1',
        ]);

        $this->helpdeskService->addReply($this->ticket, [
            'message' => $this->replyMessage,
            'is_internal' => $this->isInternal,
        ]);

        $this->replyMessage = '';
        $this->isInternal = false;

        session()->flash('success', __('Reply added successfully'));
        $this->ticket->refresh();
    }

    public function assignTicket(): void
    {
        $this->authorize('helpdesk.assign');

        $this->validate([
            'assignToUser' => 'required|exists:users,id',
        ]);

        $this->helpdeskService->assignTicket($this->ticket, $this->assignToUser);

        session()->flash('success', __('Ticket assigned successfully'));
        $this->ticket->refresh();
    }

    public function closeTicket(): void
    {
        $this->authorize('helpdesk.close');

        if (!$this->ticket->canBeClosed()) {
            session()->flash('error', __('Ticket must be resolved before closing'));
            return;
        }

        $this->helpdeskService->closeTicket($this->ticket);

        session()->flash('success', __('Ticket closed successfully'));
        $this->ticket->refresh();
    }

    public function reopenTicket(): void
    {
        $this->authorize('helpdesk.edit');

        if (!$this->ticket->canBeReopened()) {
            session()->flash('error', __('Ticket cannot be reopened'));
            return;
        }

        $this->helpdeskService->reopenTicket($this->ticket);

        session()->flash('success', __('Ticket reopened successfully'));
        $this->ticket->refresh();
    }

    public function resolveTicket(): void
    {
        $this->authorize('helpdesk.edit');

        $this->ticket->resolve();

        session()->flash('success', __('Ticket resolved successfully'));
        $this->ticket->refresh();
    }

    public function render()
    {
        // Get available agents for assignment
        $agents = User::whereHas('roles', function ($query) {
            $query->where('name', 'like', '%agent%')
                  ->orWhere('name', 'like', '%support%')
                  ->orWhere('name', 'Super Admin');
        })->get();

        // Calculate SLA compliance
        $slaCompliance = $this->helpdeskService->calculateSLA($this->ticket);

        return view('livewire.helpdesk.ticket-detail', [
            'agents' => $agents,
            'slaCompliance' => $slaCompliance,
        ]);
    }
}
