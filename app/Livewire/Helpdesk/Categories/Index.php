<?php

declare(strict_types=1);

namespace App\Livewire\Helpdesk\Categories;

use App\Http\Requests\TicketCategoryRequest;
use App\Models\TicketCategory;
use App\Models\TicketSLAPolicy;
use App\Models\User;
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
    public string $description = '';
    public ?int $parent_id = null;
    public ?int $default_assignee_id = null;
    public ?int $sla_policy_id = null;
    public string $color = '#3B82F6';
    public string $icon = '';
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
            $category = TicketCategory::findOrFail($id);
            $this->editingId = $id;
            $this->fill($category->only([
                'name',
                'name_ar',
                'description',
                'parent_id',
                'default_assignee_id',
                'sla_policy_id',
                'color',
                'icon',
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
            'name' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:ticket_categories,id',
            'default_assignee_id' => 'nullable|exists:users,id',
            'sla_policy_id' => 'nullable|exists:ticket_sla_policies,id',
            'color' => 'nullable|string|max:20',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'integer|min:0',
        ]);

        $data = [
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'description' => $this->description,
            'parent_id' => $this->parent_id,
            'default_assignee_id' => $this->default_assignee_id,
            'sla_policy_id' => $this->sla_policy_id,
            'color' => $this->color,
            'icon' => $this->icon,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];

        if ($this->editingId) {
            $category = TicketCategory::findOrFail($this->editingId);
            $data['updated_by'] = auth()->id();
            $category->update($data);
            session()->flash('success', __('Category updated successfully'));
        } else {
            $data['created_by'] = auth()->id();
            TicketCategory::create($data);
            session()->flash('success', __('Category created successfully'));
        }

        $this->closeModal();
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $category = TicketCategory::findOrFail($id);

        if ($category->hasChildren()) {
            session()->flash('error', __('Cannot delete category with subcategories'));
            return;
        }

        if ($category->tickets()->exists()) {
            session()->flash('error', __('Cannot delete category with existing tickets'));
            return;
        }

        $category->delete();
        session()->flash('success', __('Category deleted successfully'));
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        $category = TicketCategory::findOrFail($id);
        $category->is_active = !$category->is_active;
        $category->updated_by = auth()->id();
        $category->save();

        session()->flash('success', __('Category status updated'));
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->name_ar = '';
        $this->description = '';
        $this->parent_id = null;
        $this->default_assignee_id = null;
        $this->sla_policy_id = null;
        $this->color = '#3B82F6';
        $this->icon = '';
        $this->is_active = true;
        $this->sort_order = 0;
        $this->resetErrorBag();
    }

    public function render()
    {
        $categories = TicketCategory::with(['parent', 'defaultAssignee', 'slaPolicy'])
            ->withCount('tickets')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        $parentCategories = TicketCategory::whereNull('parent_id')->active()->ordered()->get();
        $agents = User::whereHas('roles', function ($query) {
            $query->where('name', 'like', '%agent%')
                  ->orWhere('name', 'like', '%support%')
                  ->orWhere('name', 'Super Admin');
        })->get();
        $slaPolicies = TicketSLAPolicy::active()->get();

        return view('livewire.helpdesk.categories.index', [
            'categories' => $categories,
            'parentCategories' => $parentCategories,
            'agents' => $agents,
            'slaPolicies' => $slaPolicies,
        ]);
    }
}
