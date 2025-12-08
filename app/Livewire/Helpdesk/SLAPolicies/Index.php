<?php

declare(strict_types=1);

namespace App\Livewire\Helpdesk\SLAPolicies;

use App\Http\Requests\TicketSLAPolicyRequest;
use App\Models\TicketSLAPolicy;
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
    public string $description = '';
    public int $response_time_minutes = 60;
    public int $resolution_time_minutes = 480;
    public bool $business_hours_only = false;
    public string $business_hours_start = '09:00';
    public string $business_hours_end = '17:00';
    public array $working_days = [1, 2, 3, 4, 5];
    public bool $is_active = true;

    public function mount(): void
    {
        $this->authorize('helpdesk.manage');
    }

    public function openModal(?int $id = null): void
    {
        $this->resetForm();

        if ($id) {
            $policy = TicketSLAPolicy::findOrFail($id);
            $this->editingId = $id;
            $this->fill($policy->only([
                'name',
                'description',
                'response_time_minutes',
                'resolution_time_minutes',
                'business_hours_only',
                'business_hours_start',
                'business_hours_end',
                'working_days',
                'is_active',
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
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'response_time_minutes' => 'required|integer|min:1',
            'resolution_time_minutes' => 'required|integer|min:1',
            'business_hours_only' => 'boolean',
        ];

        if ($this->business_hours_only) {
            $rules['business_hours_start'] = ['required', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/'];
            $rules['business_hours_end'] = ['required', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/'];
            $rules['working_days'] = 'required|array|min:1';
            $rules['working_days.*'] = 'integer|min:0|max:6';
        }

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'response_time_minutes' => $this->response_time_minutes,
            'resolution_time_minutes' => $this->resolution_time_minutes,
            'business_hours_only' => $this->business_hours_only,
            'business_hours_start' => $this->business_hours_only ? $this->business_hours_start : null,
            'business_hours_end' => $this->business_hours_only ? $this->business_hours_end : null,
            'working_days' => $this->business_hours_only ? $this->working_days : null,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            $policy = TicketSLAPolicy::findOrFail($this->editingId);
            $data['updated_by'] = auth()->id();
            $policy->update($data);
            session()->flash('success', __('SLA Policy updated successfully'));
        } else {
            $data['created_by'] = auth()->id();
            TicketSLAPolicy::create($data);
            session()->flash('success', __('SLA Policy created successfully'));
        }

        $this->closeModal();
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $policy = TicketSLAPolicy::findOrFail($id);

        if ($policy->tickets()->exists() || $policy->categories()->exists()) {
            session()->flash('error', __('Cannot delete SLA policy in use'));
            return;
        }

        $policy->delete();
        session()->flash('success', __('SLA Policy deleted successfully'));
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        $policy = TicketSLAPolicy::findOrFail($id);
        $policy->is_active = !$policy->is_active;
        $policy->updated_by = auth()->id();
        $policy->save();

        session()->flash('success', __('SLA Policy status updated'));
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->description = '';
        $this->response_time_minutes = 60;
        $this->resolution_time_minutes = 480;
        $this->business_hours_only = false;
        $this->business_hours_start = '09:00';
        $this->business_hours_end = '17:00';
        $this->working_days = [1, 2, 3, 4, 5];
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        $policies = TicketSLAPolicy::withCount(['tickets', 'categories'])
            ->paginate(20);

        $daysOfWeek = [
            0 => __('Sunday'),
            1 => __('Monday'),
            2 => __('Tuesday'),
            3 => __('Wednesday'),
            4 => __('Thursday'),
            5 => __('Friday'),
            6 => __('Saturday'),
        ];

        return view('livewire.helpdesk.sla-policies.index', [
            'policies' => $policies,
            'daysOfWeek' => $daysOfWeek,
        ]);
    }
}
