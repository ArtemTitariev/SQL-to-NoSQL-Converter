<?php

namespace App\Services\Relationships;

class ResponseHandler {
    
    public static function successResponse()
    {
        return response()->json([
            'status' => 'success',
            'message' => __('Changes saved successfully!'),
        ], 200);
    }
    
    public static function errorResponse()
    {
        return response()->json([
            'status' => 'error',
            'message' => __('Error saving changes!'),
        ], 500);
    }
    
    public static function noChangesResponse()
    {
        return response()->json([
            'status' => 'error',
            'message' => __('No changes detected.'),
        ], 409);
    }

    public static function complexRelationResponse()
    {
        return response()->json([
            'status' => 'error',
            'type' => 'complex_relation',
            'message' => "Даний зв`язок є частиною складного зв`язку.",
            'recommendation' => 'Вкладення при такому зв`язку не підтримуються.',
        ], 409);
    }

    public static function selfRefResponse($collectionName)
    {
        return response()->json([
            'status' => 'error',
            'type' => 'self_ref',
            'message' => "Колекція {$collectionName} має посилання на себе.",
            'recommendation' => 'Вкладення при такому зв`язку не підтримуються.',
        ], 409);
    }

    public static function embeddedCollectionResponse($embedds, $collectionName)
    {
        $embeddedTo = $embedds->map(fn($embedd) => [
            'id' => $embedd->fkCollection->id,
            'name' => $embedd->fkCollection->name,
        ]);

        return response()->json([
            'status' => 'error',
            'type' => 'collection_is_embedded',
            'message' => "Колекція {$collectionName} є вкладеною.",
            'embedded_to' => $embeddedTo,
            'recommendation' => 'Спочатку змініть зв`язки на посилання (Linking).',
        ], 409);
    }

    public static function linksToMainCollectionResponse($links, $collectionName)
    {
        $linkedWith = $links->map(fn($link) => [
            'id' => $link->fkCollection->id,
            'name' => $link->fkCollection->name,
        ]);

        return response()->json([
            'status' => 'error',
            'type' => 'links_to_main_collection',
            'message' => "На колекцію {$collectionName} є посилання (Linking).",
            'linked_from' => $linkedWith,
            'recommendation' => "Вкласти колекцію {$collectionName} неможливо при такому зв`язку.",
        ], 409);
    }

    public static function mainCollectionHasLinksResponse($links, $collectionName)
    {
        $linkedWith = $links->map(fn($link) => [
            'id' => $link->pkCollection->id,
            'name' => $link->pkCollection->name,
        ]);

        return response()->json([
            'status' => 'error',
            'type' => 'main_collection_has_links',
            'message' => "Колекція {$collectionName} має посилання (Linking).",
            'linkend_with' => $linkedWith,
            'recommendation' => 'Спочатку змініть зв`язки на вкладення (Embedding).',
        ], 409);
    }

    public static function manyToManyLinkResponse($collections, $collectionName)
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

        return response()->json([
            'status' => 'error',
            'type' => 'many_to_many_link',
            'message' => "Колекція {$collectionName} є частиною звязку Багато-до-Багатьох.",
            'collections' => $usedCollections,
            'recommendation' => "Вкласти колекцію {$collectionName} неможливо при такому зв`язку.",
        ], 409);
    }
}
