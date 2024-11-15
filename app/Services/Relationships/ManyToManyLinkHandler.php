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

    public function handle(ManyToManyLink $relation, MongoManyToManyRelation $relationType, bool $isTesting)
    {
        if ($relation->relation_type === $relationType) {
            return ResponseHandler::noChangesResponse();
        }

        $method = self::getMethod($relation->relation_type, $relationType);

        $messages = [
            'errors' => [],
            'warnings' => [],
        ];

        if (! method_exists(self::class, $method)) {
            throw new \LogicException('Unknown handling method for ManyToManyLink.');
        }

        return self::$method($relation, $isTesting, $messages);
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

    protected function fromPivotToEmbedding(ManyToManyLink $relation, bool $isTesting, array &$messages)
    {
        $result = $this->checkPivotLinks($relation->pivotCollection);
        if (! is_null($result)) {
            $messages['errors'][] = $result;
        }

        $result = $this->checkPivotEmbeds($relation->pivotCollection);
        if (! is_null($result)) {
            $messages['errors'][] = $result;
        }

        $response = ResponseHandler::checkAndRespond($isTesting, $messages);
        return $response ??
            $relation->changeToEmbedding();
    }

    protected function fromPivotToHybrid(ManyToManyLink $relation, bool $isTesting, array &$messages)
    {
        $result = $this->checkPivotLinks($relation->pivotCollection);
        if (! is_null($result)) {
            $messages['errors'][] = $result;
        }

        $result = $this->checkPivotEmbeds($relation->pivotCollection);
        if (! is_null($result)) {
            $messages['errors'][] = $result;
        }

        $response = ResponseHandler::checkAndRespond($isTesting, $messages);

        return $response ??
            $relation->changeToHybrid();
    }

    protected function fromEmbeddingToPivot(ManyToManyLink $relation, bool $isTesting, array &$messages)
    {
        $response = ResponseHandler::checkAndRespond($isTesting, $messages);
        
        return $response ??
            $relation->changeToLinkingWithPivot();
    }

    protected function fromEmbeddingToHybrid(ManyToManyLink $relation, bool $isTesting, array &$messages)
    {
        $response = ResponseHandler::checkAndRespond($isTesting, $messages);
        
        return $response ??
            $relation->changeToHybrid();
    }

    protected function fromHybridToPivot(ManyToManyLink $relation, bool $isTesting, array &$messages)
    {
        $response = ResponseHandler::checkAndRespond($isTesting, $messages);
        
        return $response ??
            $relation->changeToLinkingWithPivot();
    }

    protected function fromHybridToEmbedding(ManyToManyLink $relation, bool $isTesting, array &$messages)
    {
        $response = ResponseHandler::checkAndRespond($isTesting, $messages);
        
        return $response ??
            $relation->changeToEmbedding();
    }

    private function checkPivotLinks(Collection $pivot)
    {
        $linksFrom = $this->relationService->checkLinksIn($pivot->id);
        if ($linksFrom->isNotEmpty()) {
            return ResponseHandler::prepareMainCollectionHasLinksResponse(
                $linksFrom,
                $pivot->name
            );
        }

        $linksTo = $this->relationService->checkLinksTo($pivot->id);
        if ($linksTo->isNotEmpty()) {
            return ResponseHandler::prepareLinksToMainCollectionResponse(
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
            return ResponseHandler::prepareEmbeddedCollectionResponse(
                $embeddedTo,
                $pivot->name
            );
        }

        $embeddsTo = $this->relationService->checkEmbeddings(
            $pivot->id,
            LinkEmbedd::MAIN_IN_RELATED
        );

        if ($embeddsTo->isNotEmpty()) {
            return ResponseHandler::prepareEmbeddedCollectionResponse(
                $embeddsTo,
                $pivot->name
            );
        }

        return null;
    }
}
