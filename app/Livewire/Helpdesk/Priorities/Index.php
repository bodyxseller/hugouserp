<?php

declare(strict_types=1);

namespace App\Livewire\Helpdesk\Priorities;

use App\Http\Requests\TicketPriorityRequest;
use App\Models\TicketPriority;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $name_ar = '';
    public int $level = 1;
    public string $color = '#3B82F6';
    public int $response_time_minutes = 60;
    public int $resolution_time_minutes = 480;
    public bool $is_active = true;
    public int $sort_order = 0;

    public function mount(): void
    {
        $this->authorize('helpdesk.manage');
    }

    public function openModal(?int $id = null): void
    {
        $this->resetForm();

        if ($id) {
            $priority = TicketPriority::findOrFail($id);
            $this->editingId = $id;
            $this->fill($priority->only([
                'name',
                'name_ar',
                'level',
                'color',
                'response_time_minutes',
                'resolution_time_minutes',
                'is_active',
                'sort_order',
            ]));
        }

        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:100',
            'name_ar' => 'nullable|string|max:100',
            'level' => 'required|integer|min:1|max:5',
            'color' => 'nullable|string|max:20',
            'response_time_minutes' => 'required|integer|min:1',
            'resolution_time_minutes' => 'required|integer|min:1',
            'sort_order' => 'integer|min:0',
        ]);

        $data = [
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'level' => $this->level,
            'color' => $this->color,
            'response_time_minutes' => $this->response_time_minutes,
            'resolution_time_minutes' => $this->resolution_time_minutes,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];

        if ($this->editingId) {
            $priority = TicketPriority::findOrFail($this->editingId);
            $priority->update($data);
            session()->flash('success', __('Priority updated successfully'));
        } else {
            TicketPriority::create($data);
            session()->flash('success', __('Priority created successfully'));
        }

        $this->closeModal();
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $priority = TicketPriority::findOrFail($id);

        if ($priority->tickets()->exists()) {
            session()->flash('error', __('Cannot delete priority with existing tickets'));
            return;
        }

        $priority->delete();
        session()->flash('success', __('Priority deleted successfully'));
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        $priority = TicketPriority::findOrFail($id);
        $priority->is_active = !$priority->is_active;
        $priority->save();

        session()->flash('success', __('Priority status updated'));
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->name_ar = '';
        $this->level = 1;
        $this->color = '#3B82F6';
        $this->response_time_minutes = 60;
        $this->resolution_time_minutes = 480;
        $this->is_active = true;
        $this->sort_order = 0;
        $this->resetErrorBag();
    }

    public function render()
    {
        $priorities = TicketPriority::withCount('tickets')
            ->orderBy('level')
            ->paginate(20);

        return view('livewire.helpdesk.priorities.index', [
            'priorities' => $priorities,
        ]);
    }
}
