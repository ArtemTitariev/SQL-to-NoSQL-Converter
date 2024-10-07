<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use App\Enums\RelationType;

class RelationTypeBadge extends Component
{
    public $relationType;

    /**
     * Create a new component instance.
     */
    public function __construct(RelationType $relationType)
    {
        $this->relationType = $relationType;
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
        return match($this->relationType) {
            RelationType::ONE_TO_ONE => 'text-info',
            RelationType::ONE_TO_MANY => 'text-accent',
            RelationType::MANY_TO_ONE => 'text-primary',
            RelationType::MANY_TO_MANY => 'text-secondary',
            RelationType::SELF_REF => 'text-amber-700',
            RelationType::COMPLEX => 'text-danger',
            default => 'text-accent',
        };
    }

    public function getRelationDescription()
    {
        return match($this->relationType) {
            RelationType::ONE_TO_ONE => __('One To One'),
            RelationType::ONE_TO_MANY => __('One To Many'),
            RelationType::MANY_TO_ONE => __('Many To One'),
            RelationType::MANY_TO_MANY => __('Many To Many'),
            RelationType::SELF_REF => __('Self reference'),
            RelationType::COMPLEX => __('Part of a complex relationship'),
            default => (string) $this->relationType->value,
        };
    }
}
