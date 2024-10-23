<?php

namespace App\Http\Controllers;

use App\Enums\MongoManyToManyRelation;
use App\Enums\MongoRelationType;
use App\Http\Requests\UpdateRelationshipRequest;
use App\Models\Convert;
use App\Models\MongoSchema\LinkEmbedd;
use App\Models\MongoSchema\ManyToManyLink;
use App\Services\Relationships\CollectionRelationService;
use App\Services\Relationships\LinkEmbedHandler;
use App\Services\Relationships\ManyToManyLinkHandler;
use App\Services\Relationships\ResponseHandler;

class RelationshipController extends Controller
{
    protected $relationService;

    public function __construct(CollectionRelationService $relationService)
    {
        $this->relationService = $relationService;
    }

    public function edit(UpdateRelationshipRequest $request, Convert $convert)
    {
        $validated = $request->validated();
        $decodedRelation = $request->decodedRelationData; //from "after" validation method

        $relation = $decodedRelation['model']::findOrFail($decodedRelation['id']);

        $isTesting = $validated['mode'] === 'testing'; // only validate or validate and save

        if ($relation instanceof LinkEmbedd) {
            $relationType = MongoRelationType::tryFrom($validated['relationTypeLinkEmbedd']);
            $handler = new LinkEmbedHandler($this->relationService);

            return $this->handleResult(
                $handler->handle($relation, $relationType, $request->input('embedInMain'), $isTesting)
            );
        } elseif ($relation instanceof ManyToManyLink) {
            $relationType = MongoManyToManyRelation::tryFrom($validated['relationTypeManyToMany']);
            $handler = new ManyToManyLinkHandler($this->relationService);

            return $this->handleResult(
                $handler->handle($relation, $relationType, $isTesting)
            );
        }

        throw new \LogicException('Unsupported relation type');
    }

    protected function handleResult($result)
    {
        if ($result === true) {
            return ResponseHandler::successResponse();
        } elseif ($result === false) {
            return ResponseHandler::errorResponse();
        } else {
            return $result;
        }
    }
}
