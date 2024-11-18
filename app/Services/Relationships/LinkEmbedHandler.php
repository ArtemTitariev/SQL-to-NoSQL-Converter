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

    public function handle(LinkEmbedd $relation, MongoRelationType $relationType, ?bool $embedInMain = null, bool $isTesting = false)
    {
        $messages = [
            'errors' => [],
            'warnings' => [],
        ];

        switch ($relationType) {
            case MongoRelationType::LINKING:
                return $this->handleLinking($relation, $isTesting, $messages);

            case MongoRelationType::EMBEDDING:
                return $this->handleEmbedding($relation, $embedInMain, $isTesting, $messages);

            default:
                throw new \LogicException('Unknown handling method for LinkEmbedd.');
        }
    }

    protected function handleLinking(LinkEmbedd $relation, bool $isTesting, array &$messages)
    {
        if ($relation->relation_type === MongoRelationType::LINKING) {
            return ResponseHandler::noChangesResponse();
        }

        $mainEmbeddedTo = $this->relationService->checkEmbeddings(
            $relation->fk_collection_id,
            LinkEmbedd::MAIN_IN_RELATED
        );

        if ($mainEmbeddedTo->isNotEmpty()) {
            $messages["warnings"][] = ResponseHandler::prepareEmbeddedCollectionResponse(
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
            $messages["warnings"][]  = ResponseHandler::prepareEmbeddedCollectionResponse(
                $embeddsToRelated,
                $relation->pkCollection()->value('name')
            );
        }

        $response = ResponseHandler::checkAndRespond($isTesting, $messages);

        return $response ??
            $relation->changeToLinking();
    }

    protected function handleEmbedding(LinkEmbedd $relation, bool $embedInMain, bool $isTesting, array &$messages)
    {
        if ($this->relationService->hasComplexRelation($relation)) {
            $responseContent = ResponseHandler::prepareComplexRelationResponse();
            $messages["errors"][] = $responseContent;
        }

        $collection = $embedInMain ? $relation->pkCollection : $relation->fkCollection;

        if ($this->relationService->hasCircularRef($collection)) {
            $responseContent = ResponseHandler::prepareCircularRefResponse($collection->name);
            $messages["errors"][] = $responseContent;
        }

        if ($relation->relation_type === MongoRelationType::EMBEDDING) {
            return $this->handleExistingEmbedding($relation, $embedInMain, $isTesting, $messages);
        }

        return $this->changeToEmbedding($relation, $embedInMain, $isTesting, $messages);
    }

    protected function handleExistingEmbedding(LinkEmbedd $relation, bool $embedInMain, bool $isTesting, array &$messages)
    {
        if ($relation->embed_in_main === $embedInMain) {
            return ResponseHandler::noChangesResponse();
        }

        return $this->changeEmbeddingDirection($relation, $embedInMain, $isTesting, $messages);
    }

    protected function changeEmbeddingDirection(LinkEmbedd $relation, bool $embedInMain, bool $isTesting, array &$messages)
    {
        $this->checkEmbedding($relation, $embedInMain, $messages);
        
        // $collection = $embedInMain ? $relation->pkCollection : $relation->fkCollection;

        // if ($this->relationService->hasSelfRef($collection->id)) {
        //     $responseContent = ResponseHandler::prepareSelfRefResponse($collection->name);
        //     $messages['errors'][] = $responseContent;
        // }

        // $linksFrom = $this->relationService->checkLinksIn($collection->id);
        // if ($linksFrom->isNotEmpty()) {
        //     $responseContent = ResponseHandler::prepareMainCollectionHasLinksResponse($linksFrom, $collection->name);
        //     $messages['warnings'][] = $responseContent;
        // }

        // $linksTo = $this->relationService->checkLinksTo($collection->id);
        // if ($linksTo->isNotEmpty()) {
        //     $responseContent = ResponseHandler::prepareLinksToMainCollectionResponse(
        //         $linksTo,
        //         $collection->name
        //     );
        //     $messages['warnings'][] = $responseContent;
        // }

        // $nn = $this->relationService->checkManyToManyLinks($collection->id);
        // if ($nn->isNotEmpty()) {

        //     $responseContent = ResponseHandler::prepareManyToManyLinkResponse(
        //         $nn,
        //         $collection->name
        //     );
        //     $messages['errors'][] = $responseContent;
        // }

        $response = ResponseHandler::checkAndRespond($isTesting, $messages);

        return $response ??
            $relation->changeEmbeddingDirection();
    }

    protected function changeToEmbedding(LinkEmbedd $relation, bool $embedInMain, bool $isTesting, array &$messages)
    {
        $this->checkEmbedding($relation, $embedInMain, $messages);
        
        // $collection = $embedInMain ? $relation->pkCollection : $relation->fkCollection;

        // if ($this->relationService->hasSelfRef($collection->id)) {
        //     $responseContent = ResponseHandler::prepareSelfRefResponse($collection->name);
        //     $messages['errors'][] = $responseContent;
        // }

        // $linksFrom = $this->relationService->checkLinksIn($collection->id, $relation->id);
        // if ($linksFrom->isNotEmpty()) {
        //     $responseContent = ResponseHandler::prepareMainCollectionHasLinksResponse(
        //         $linksFrom,
        //         $collection->name
        //     );
        //     $messages['warnings'][] = $responseContent;
        // }

        // $linksTo = $this->relationService->checkLinksTo($collection->id, $relation->id);
        // if ($linksTo->isNotEmpty()) {
        //     $responseContent =  ResponseHandler::prepareLinksToMainCollectionResponse(
        //         $linksTo,
        //         $collection->name
        //     );
        //     $messages['warnings'][] = $responseContent;
        // }

        // $nn = $this->relationService->checkManyToManyLinks($collection->id);
        // if ($nn->isNotEmpty()) {
        //     $responseContent = ResponseHandler::prepareManyToManyLinkResponse(
        //         $nn,
        //         $collection->name
        //     );
        //     $messages['errors'][] = $responseContent;
        // }

        $response = ResponseHandler::checkAndRespond($isTesting, $messages);

        return $response ??
            $relation->changeToEmbedding($embedInMain);
    }

    protected function checkEmbedding(LinkEmbedd $relation, bool $embedInMain, array &$messages)
    {
        $collection = $embedInMain ? $relation->pkCollection : $relation->fkCollection;

        if ($this->relationService->hasSelfRef($collection->id)) {
            $responseContent = ResponseHandler::prepareSelfRefResponse($collection->name);
            $messages['errors'][] = $responseContent;
        }

        $linksFrom = $this->relationService->checkLinksIn($collection->id, $relation->id);
        if ($linksFrom->isNotEmpty()) {
            $responseContent = ResponseHandler::prepareMainCollectionHasLinksResponse(
                $linksFrom,
                $collection->name
            );
            $messages['warnings'][] = $responseContent;
        }

        $linksTo = $this->relationService->checkLinksTo($collection->id, $relation->id);
        if ($linksTo->isNotEmpty()) {
            $responseContent =  ResponseHandler::prepareLinksToMainCollectionResponse(
                $linksTo,
                $collection->name
            );
            $messages['warnings'][] = $responseContent;
        }

        $nn = $this->relationService->checkManyToManyLinks($collection->id);
        if ($nn->isNotEmpty()) {
            $responseContent = ResponseHandler::prepareManyToManyLinkResponse(
                $nn,
                $collection->name
            );
            $messages['errors'][] = $responseContent;
        }
    }
}
