<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class RelationTypeBadge extends Component
{
    public $relationType;

    public $badgeClass;

    public $relationDescription;

    private $relationTypes;
    /**
     * Create a new component instance.
     */
    public function __construct($relationType)
    {
        $this->relationType = $relationType;
        $this->relationTypes = config('constants.RELATION_TYPES');
        $this->badgeClass = $this->getBadgeClass();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.relation-type-badge');
    }

    public function getBadgeClass()
    {
        $classes = [
            $this->relationTypes['ONE-TO-ONE'] => 'text-accent',
            $this->relationTypes['ONE-TO-MANY'] => 'text-primary',
            $this->relationTypes['MANY-TO-MANY'] => 'text-secondary',
            $this->relationTypes['SELF-REF'] => 'text-warning',
            $this->relationTypes['COMPLEX'] => 'text-danger',
        ];

        return $classes[$this->relationType] ?? 'text-accent';
    }

    public function getRelationDescription()
    {
        switch ($this->relationType) {
            case $this->relationTypes['ONE-TO-ONE']:
                return __('One To One');
            case $this->relationTypes['ONE-TO-MANY']:
                return __('Many To One');
            case $this->relationTypes['MANY-TO-MANY']:
                return __('Many To Many');
            case $this->relationTypes['SELF-REF']:
                return __('Self reference');
            case $this->relationTypes['COMPLEX']:
                return __('Part of a complex relationship');
            default:
                return $this->relationType;
        }
    }
}
