<?php

namespace App\Services\Relationships;

use App\Models\MongoSchema\LinkEmbedd;
use App\Models\MongoSchema\ManyToManyLink;
use App\Enums\RelationType;
use App\Enums\MongoRelationType;
use App\Models\MongoSchema\Collection;
use App\Models\SQLSchema\CircularRef;

class CollectionRelationService
{
    public function hasSelfRef($collectionId): bool
    {
        return LinkEmbedd::where('fk_collection_id', $collectionId)
            ->where('sql_relation', RelationType::SELF_REF->value)
            ->exists();
    }

    public function hasCircularRef(Collection $collection): bool
    {
        $sqlTable = $collection->sqlTable;
        return CircularRef::checkIfExistsByTableName(
            $sqlTable->sql_database_id,
            $sqlTable->name
        );
    }

    public function hasComplexRelation(LinkEmbedd $relation)
    {
        return $relation->sql_relation->isComplex();
    }

    public function checkEmbeddings($collectionId, string $direction, $excludeCollectionId = null)
    {
        $query = LinkEmbedd::where('relation_type', MongoRelationType::EMBEDDING->value)
            ->where('pk_collection_id', $collectionId)
            ->where('embed_in_main', $direction === LinkEmbedd::RELATED_IN_MAIN);

        if (! is_null($excludeCollectionId)) {
            $query->where('fk_collection_id', '<>', $excludeCollectionId);
        }

        return $query->get();
    }

    public function checkLinksIn($collectionId, $excludeRelationId = null)
    {
        $query = LinkEmbedd::where('fk_collection_id', $collectionId)
            ->where('relation_type', MongoRelationType::LINKING->value);

        if (! is_null($excludeRelationId)) {
            $query->where('id', '<>', $excludeRelationId);
        }

        return $query->get();
    }

    public function checkLinksTo($collectionId, $excludeRelationId = null)
    {
        $query = LinkEmbedd::where('pk_collection_id', $collectionId)
            ->where('relation_type', MongoRelationType::LINKING->value);

        if (! is_null($excludeRelationId)) {
            $query->where('id', '<>', $excludeRelationId);
        }

        return $query->get();
    }

    public function checkManyToManyLinks($collectionId)
    {
        return ManyToManyLink::where('collection1_id', $collectionId)
            ->orWhere('collection2_id', $collectionId)
            ->orWhere('pivot_collection_id', $collectionId)
            ->get();
    }
}
