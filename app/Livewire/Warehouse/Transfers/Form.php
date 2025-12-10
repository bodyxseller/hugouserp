<?php
declare(strict_types=1);
namespace App\Livewire\Warehouse\Transfers;
use Livewire\Attributes\Layout;
use Livewire\Component;
#[Layout('layouts.app')]
class Form extends Component
{
    public function mount(): void { $this->authorize('warehouse.manage'); }
    public function render() { return view('livewire.warehouse.transfers.form'); }
}
