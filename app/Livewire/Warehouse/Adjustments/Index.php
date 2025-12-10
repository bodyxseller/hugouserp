<?php
declare(strict_types=1);
namespace App\Livewire\Warehouse\Adjustments;
use Livewire\Attributes\Layout;
use Livewire\Component;
#[Layout('layouts.app')]
class Index extends Component
{
    public function mount(): void { $this->authorize('warehouse.view'); }
    public function render() { return view('livewire.warehouse.adjustments.index'); }
}
