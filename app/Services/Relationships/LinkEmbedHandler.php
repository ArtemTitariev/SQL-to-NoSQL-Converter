<?php

namespace App\Services\Relationships;

use App\Enums\MongoRelationType;
use App\Models\MongoSchema\LinkEmbedd;

class LinkEmbedHandler
{
    protected CollectionRelationService $relationService;

    public function __construct(CollectionRelationService $relationService)
    {
        $this->relationService = $relationService;
    }

    public function handle(LinkEmbedd $relation, MongoRelationType $relationType, ?bool $embedInMain = null)
    {
        switch ($relationType) {
            case MongoRelationType::LINKING:
                return $this->handleLinking($relation);

            case MongoRelationType::EMBEDDING:
                return $this->handleEmbedding($relation, $embedInMain);

            default:
                throw new \LogicException('Unknown handling method for LinkEmbedd.');
        }
    }

    protected function handleLinking(LinkEmbedd $relation)
    {
        if ($relation->relation_type === MongoRelationType::LINKING) {
            return ResponseHandler::noChangesResponse();
        }

        $mainEmbeddedTo = $this->relationService->checkEmbeddings(
            $relation->fk_collection_id,
            LinkEmbedd::MAIN_IN_RELATED
        );

        if ($mainEmbeddedTo->isNotEmpty()) {
            return ResponseHandler::embeddedCollectionResponse(
                $mainEmbeddedTo,
                $relation->fkCollection()->value('name')
            );
        }

        $embeddsToRelated = $this->relationService->checkEmbeddings(
            $relation->pk_collection_id,
            LinkEmbedd::RELATED_IN_MAIN,
            $relation->fk_collection_id
        );

        if ($embeddsToRelated->isNotEmpty()) {
            return ResponseHandler::embeddedCollectionResponse(
                $embeddsToRelated,
                $relation->pkCollection()->value('name')
            );
        }

        return $relation->changeToLinking();
    }

    protected function handleEmbedding(LinkEmbedd $relation, bool $embedInMain)
    {
        if ($this->relationService->hasComplexRelation($relation)) {
            return ResponseHandler::complexRelationResponse();
        }

        if ($relation->relation_type === MongoRelationType::EMBEDDING) {
            return $this->handleExistingEmbedding($relation, $embedInMain);
        }

        return $this->changeToEmbedding($relation, $embedInMain);
    }

    protected function handleExistingEmbedding(LinkEmbedd $relation, bool $embedInMain)
    {
        if ($relation->embed_in_main === $embedInMain) {
            return ResponseHandler::noChangesResponse();
        }

        return $this->changeEmbeddingDirection($relation, $embedInMain);
    }

    protected function changeEmbeddingDirection(LinkEmbedd $relation, bool $embedInMain)
    {
        $collection = $embedInMain ? $relation->pkCollection : $relation->fkCollection;

        if ($this->relationService->hasSelfRef($collection->id)) {
            return ResponseHandler::selfRefResponse($collection->name);
        }

        $linksFrom = $this->relationService->checkLinksIn($collection->id);
        if ($linksFrom->isNotEmpty()) {
            return ResponseHandler::mainCollectionHasLinksResponse(
                $linksFrom,
                $collection->name
            );
        }

        $linksTo = $this->relationService->checkLinksTo($collection->id);
        if ($linksTo->isNotEmpty()) {
            return ResponseHandler::linksToMainCollectionResponse(
                $linksTo,
                $collection->name
            );
        }

        $nn = $this->relationService->checkManyToManyLinks($collection->id);
        if ($nn->isNotEmpty()) {
            return ResponseHandler::manyToManyLinkResponse(
                $nn,
                $collection->name
            );
        }

        return $relation->changeEmbeddingDirection();
    }

    protected function changeToEmbedding(LinkEmbedd $relation, bool $embedInMain)
    {
        $collection = $embedInMain ? $relation->pkCollection : $relation->fkCollection;

        if ($this->relationService->hasSelfRef($collection->id)) {
            return ResponseHandler::selfRefResponse($collection->name);
        }

        $linksFrom = $this->relationService->checkLinksIn($collection->id, $relation->id);
        if ($linksFrom->isNotEmpty()) {
            return ResponseHandler::mainCollectionHasLinksResponse(
                $linksFrom,
                $collection->name
            );
        }

        $linksTo = $this->relationService->checkLinksTo($collection->id, $relation->id);
        if ($linksTo->isNotEmpty()) {
            return ResponseHandler::linksToMainCollectionResponse(
                $linksTo,
                $collection->name
            );
        }

        $nn = $this->relationService->checkManyToManyLinks($collection->id);
        if ($nn->isNotEmpty()) {
            return ResponseHandler::manyToManyLinkResponse(
                $nn,
                $collection->name
            );
        }

        return $relation->changeToEmbedding($embedInMain);
    }
}
