<?php

namespace App\Services\Relationships;

use App\Enums\MongoManyToManyRelation;
use App\Models\MongoSchema\Collection;
use App\Models\MongoSchema\LinkEmbedd;
use App\Models\MongoSchema\ManyToManyLink;

class ManyToManyLinkHandler
{

    protected CollectionRelationService $relationService;

    public function __construct(CollectionRelationService $relationService)
    {
        $this->relationService = $relationService;
    }

    public function handle(ManyToManyLink $relation, MongoManyToManyRelation $relationType)
    {
        $method = self::getMethod($relation->relation_type, $relationType);

        // dd($relation, $relationType, $method);

        if (method_exists(self::class, $method)) {
            return self::$method($relation);
        }

        throw new \LogicException('Unknown handling method for ManyToManyLink.');
    }

    protected function getMethod($oldRelationType, $newRelationType)
    {
        $map = [
            $oldRelationType::LINKING_WITH_PIVOT->value => [
                $newRelationType::EMBEDDING->value => 'fromPivotToEmbedding',
                $newRelationType::HYBRID->value => 'fromPivotToHybrid',
            ],
            $oldRelationType::EMBEDDING->value => [
                $newRelationType::LINKING_WITH_PIVOT->value => 'fromEmbeddingToPivot',
                $newRelationType::HYBRID->value => 'fromEmbeddingToHybrid',
            ],
            $oldRelationType::HYBRID->value => [
                $newRelationType::LINKING_WITH_PIVOT->value => 'fromHybridToPivot',
                $newRelationType::EMBEDDING->value => 'fromHybridToEmbedding',
            ],
        ];

        return $map[$oldRelationType->value][$newRelationType->value] ?? null;
    }

    protected function fromPivotToEmbedding(ManyToManyLink $relation)
    {
        $result = $this->checkPivotLinks($relation->pivotCollection);
        if (! is_null($result)) {
            return $result;
        }
        
        $result = $this->checkPivotEmbeds($relation->pivotCollection);
        if (! is_null($result)) {
            return $result;
        }

        return $relation->changeToEmbedding();
    }

    protected function fromPivotToHybrid(ManyToManyLink $relation)
    {
        $result = $this->checkPivotLinks($relation->pivotCollection);
        if (! is_null($result)) {
            return $result;
        }

        $result = $this->checkPivotEmbeds($relation->pivotCollection);
        if (! is_null($result)) {
            return $result;
        }

        return $relation->changeToHybrid();
    }

    protected function fromEmbeddingToPivot(ManyToManyLink $relation)
    {
        return $relation->changeToLinkingWithPivot();
    }

    protected function fromEmbeddingToHybrid(ManyToManyLink $relation)
    {
        return $relation->changeToHybrid();
    }

    protected function fromHybridToPivot(ManyToManyLink $relation)
    {
        return $relation->changeToLinkingWithPivot();
    }

    protected function fromHybridToEmbedding(ManyToManyLink $relation)
    {
        return $relation->changeToEmbedding();
    }

    private function checkPivotLinks(Collection $pivot)
    {
        $linksFrom = $this->relationService->checkLinksIn($pivot->id);
        if ($linksFrom->isNotEmpty()) {
            return ResponseHandler::mainCollectionHasLinksResponse(
                $linksFrom,
                $pivot->name
            );
        }

        $linksTo = $this->relationService->checkLinksTo($pivot->id);
        if ($linksTo->isNotEmpty()) {
            return ResponseHandler::linksToMainCollectionResponse(
                $linksTo,
                $pivot->name
            );
        }

        return null;
    }

    private function checkPivotEmbeds(Collection $pivot)
    {
        $embeddedTo = $this->relationService->checkEmbeddings(
            $pivot->id,
            LinkEmbedd::RELATED_IN_MAIN
        );

        if ($embeddedTo->isNotEmpty()) {
            return ResponseHandler::embeddedCollectionResponse(
                $embeddedTo,
                $pivot->name
            );
        }

        $embeddsTo = $this->relationService->checkEmbeddings(
            $pivot->id,
            LinkEmbedd::MAIN_IN_RELATED
        );

        if ($embeddsTo->isNotEmpty()) {
            return ResponseHandler::embeddedCollectionResponse(
                $embeddsTo,
                $pivot->name
            );
        }

        return null;
    }
}
