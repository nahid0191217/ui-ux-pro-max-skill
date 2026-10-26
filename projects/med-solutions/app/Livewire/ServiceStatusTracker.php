<?php

namespace App\Livewire;

use App\Models\ServiceRequest;
use Illuminate\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ServiceStatusTracker extends Component
{
    #[Validate('required|string|min:5|max:24')]
    public string $trackingCode = '';

    public ?ServiceRequest $foundRequest = null;
    public bool $searched = false;

    public function search(): void
    {
        $this->validate();
        $this->searched = true;

        $this->foundRequest = ServiceRequest::with('assignedStaff')
            ->where('tracking_code', strtoupper(trim($this->trackingCode)))
            ->first();
    }

    public function render(): View
    {
        return view('livewire.service-status-tracker');
    }
}
