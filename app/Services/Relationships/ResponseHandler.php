<?php

namespace App\Services\Relationships;

class ResponseHandler
{

    private static function unicodeResponse($data, $code)
    {
        return response()->json($data, $code, [], JSON_UNESCAPED_UNICODE);
    }
    public static function successResponse()
    {
        return static::unicodeResponse([
            'status' => 'success',
            'message' => __('Changes saved successfully!'),
        ], 200);
    }

    public static function testingSuccessResponse()
    {
        return static::unicodeResponse([
            'status' => 'success',
            'message' => __('Validation was successful! You can now save your changes.'),
        ], 200);
    }

    public static function errorResponse()
    {
        return static::unicodeResponse([
            'status' => 'error',
            'errors' => [__('Error saving changes!')],
        ], 500);
    }

    public static function messageResponse($messages, $code = 200, $status = "success")
    {
        return static::unicodeResponse([
            'status' => $status,
            'warnings' => $messages['warnings'],
            'errors' => $messages['errors'],
        ], $code);
    }

    public static function noChangesResponse()
    {
        return static::unicodeResponse([
            'status' => 'no_changes',
            'message' => __('No changes detected.'),
        ], 409);
    }

    public static function prepareComplexRelationResponse()
    {
        return [
            'status' => 'error',
            'type' => 'complex_relation',
            'message' => __('This relationship is part of a complex relationship.'),
            'recommendation' => __('Embeddings are not supported with this relationships.'),
        ];
    }

    public static function prepareCircularRefResponse($collectionName)
    {
        return [
            'status' => 'error',
            'type' => 'circular_reference',
            'message' => __('The :collectionName collection is part of a circular dependency.', ['collectionName' => $collectionName]),
            'recommendation' => __('Embeddings are not supported with this relationships.'),
        ];
    }

    public static function prepareSelfRefResponse($collectionName)
    {
        return [
            'status' => 'error',
            'type' => 'self_ref',
            'message' => __('The :collectionName collection has a self-reference.', ['collectionName' => $collectionName]),
            'recommendation' => __('Embeddings are not supported with this relationships.'),
        ];
    }

    public static function prepareEmbeddedCollectionResponse($embedds, $collectionName)
    {
        $embeddedTo = $embedds->map(fn($embedd) => [
            'id' => $embedd->fkCollection->id,
            'name' => $embedd->fkCollection->name,
        ]);

        return [
            'status' => 'warning',
            'type' => 'collection_is_embedded',
            'message' => __('The :collectionName collection is emdedded. Queries may be slowed down by the complex structure and the need to expand documents.', ['collectionName' => $collectionName]),
            'related_collections' => $embeddedTo,
            'recommendation' => __('Consider changing relationships to references for better performance and easier queries.'),
        ];
    }

    public static function prepareLinksToMainCollectionResponse($links, $collectionName)
    {
        $linkedWith = $links->map(fn($link) => [
            'id' => $link->fkCollection->id,
            'name' => $link->fkCollection->name,
        ]);

        return [
            'status' => 'warning',
            'type' => 'links_to_main_collection',
            'message' => __('The :collectionName collection is linked. Changing to an embedding can complicate queries.', ['collectionName' => $collectionName]),
            'related_collections' => $linkedWith,
            'recommendation' => __('Consider saving links to avoid complex queries.'),
        ];
    }

    public static function prepareMainCollectionHasLinksResponse($links, $collectionName)
    {
        $linkedWith = $links->map(fn($link) => [
            'id' => $link->pkCollection->id,
            'name' => $link->pkCollection->name,
        ]);

        return [
            'status' => 'warning',
            'type' => 'main_collection_has_links',
            'message' => __('The :collectionName collection has link(s). If it becomes embedded, queries may be slowed down by the need to expand documents.', ['collectionName' => $collectionName]),
            'related_collections' => $linkedWith,
            'recommendation' => __('Consider saving links to avoid complex queries.'),
        ];
    }

    public static function prepareManyToManyLinkResponse($collections, $collectionName)
    {
        $usedCollections = $collections->map(fn($rel) => [
            'first' => [
                'id' => $rel->collection1->id,
                'name' => $rel->collection1->name,
            ],
            'second' => [
                'id' => $rel->collection2->id,
                'name' => $rel->collection2->name,

            ],
            'pivot' => [
                'id' => $rel->pivotCollection->id,
                'name' => $rel->pivotCollection->name,
            ],
        ]);

        return [
            'status' => 'error',
            'type' => 'many_to_many_link',
            'message' => __('The :collectionName collection is part of a Many-to-Many relationship.', ['collectionName' => $collectionName]),
            'related_collections' => $usedCollections,
            'recommendation' => __('Embeddings are not allowed with this relationship.'),
        ];
    }
}
