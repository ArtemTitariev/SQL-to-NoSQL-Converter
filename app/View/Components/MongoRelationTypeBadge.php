<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use App\Enums\MongoRelationType;

class MongoRelationTypeBadge extends Component
{
    public $relationType;

    /**
     * Create a new component instance.
     */
    public function __construct(MongoRelationType $relationType)
    {
        $this->relationType = $relationType;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.mongo-relation-type-badge');
    }

    public function getBadgeClass()
    {
        return match($this->relationType) {
            MongoRelationType::LINKING => 'text-violet-700',
            MongoRelationType::EMBEDDING => 'text-amber-700',
            default => 'text-accent',
        };
    }

    public function getRelationDescription()
    {
        return match($this->relationType) {
            MongoRelationType::LINKING => __('Linking'),
            MongoRelationType::EMBEDDING => __('Embedding'),
            default => (string) $this->relationType->value,
        };
    }
}
