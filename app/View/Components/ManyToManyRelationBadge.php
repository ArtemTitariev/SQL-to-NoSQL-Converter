<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use App\Enums\MongoManyToManyRelation;

class ManyToManyRelationBadge extends Component
{
    public $relationType;

    /**
     * Create a new component instance.
     */
    public function __construct(MongoManyToManyRelation $relationType)
    {
        $this->relationType = $relationType;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.many-to-many-relation-badge');
    }

    public function getBadgeClass()
    {
        return match($this->relationType) {
            MongoManyToManyRelation::LINKING_WITH_PIVOT  => 'text-indigo-700',
            MongoManyToManyRelation::EMBEDDING => 'text-teal-700',
            MongoManyToManyRelation::HYBRID => 'text-fuchsia-700',
            default => 'text-accent',
        };
    }

    public function getRelationDescription()
    {
        return match($this->relationType) {
            MongoManyToManyRelation::LINKING_WITH_PIVOT => __('Linking with pivot'),
            MongoManyToManyRelation::EMBEDDING => __('Embedding'),
            MongoManyToManyRelation::HYBRID => __('Array of references'),
            default => (string) $this->relationType->value,
        };
    }
}
