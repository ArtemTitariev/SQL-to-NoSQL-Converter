<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class StatusBadge extends Component
{
    public $status;

    public $badgeClass;

    /**
     * Create a new component instance.
     */
    public function __construct($status)
    {
        $this->status = $status;
        $this->badgeClass = $this->getBadgeClass();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.status-badge');
    }

    public function getBadgeClass()
    {
        $classes = [
            \App\Models\Convert::STATUSES['CONFIGURING'] => 'text-light bg-secondary font-serif',
            \App\Models\Convert::STATUSES['PENDING'] => 'text-light bg-warning font-serif',
            \App\Models\Convert::STATUSES['IN_PROGRESS'] => 'text-light bg-secondary font-serif',
            \App\Models\Convert::STATUSES['COMPLETED'] => 'text-light bg-success font-serif',
            \App\Models\Convert::STATUSES['ERROR'] => 'text-light bg-danger font-serif',
            // The same for \App\Models\ConversionProgress
        ];

        return $classes[$this->status] ?? 'text-light bg-gray font-serif';
    }
}
