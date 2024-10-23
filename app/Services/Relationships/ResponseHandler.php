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
            'message' => 'Валідація пройшла успішно!',
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
            'message' => "Даний зв`язок є частиною складного зв`язку.",
            'recommendation' => 'Вкладення при такому зв`язку не підтримуються.',
        ];
    }

    public static function prepareCircularRefResponse($collectionName)
    {
        return [
            'status' => 'error',
            'type' => 'circular_reference',
            'message' => "Колекція {$collectionName} є частиною кругової залежності.",
            'recommendation' => 'Вкладення при такому зв`язку не підтримуються.',
        ];
    }

    public static function prepareSelfRefResponse($collectionName)
    {
        return [
            'status' => 'error',
            'type' => 'self_ref',
            'message' => "Колекція {$collectionName} має посилання на себе.",
            'recommendation' => 'Вкладення при такому зв`язку не підтримуються.',
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
            'message' => "Колекція {$collectionName} є вкладеною. Запити можуть сповільнюватись через складну структуру та необхідність розгортання документів.",
            'related_collections' => $embeddedTo,
            'recommendation' => 'Спробуйте змінити зв`язки на посилання для кращої продуктивності та простоти запитів.',
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
            'message' => "На колекцію {$collectionName} є посилання. Зміна на вкладення може ускладнити запити.",
            'related_collections' => $linkedWith,
            'recommendation' => "Розгляньте можливість збереження посилань для уникнення складних запитів.",
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
            'message' => "Колекція {$collectionName} має посилання. Якщо вона стане вкладеною, запити можуть сповільнюватись через необхідність розгортання документів",
            'related_collections' => $linkedWith,
            'recommendation' => 'Розгляньте можливість збереження посилань для уникнення складних запитів. Або змініть наявні посилання на вкладення.',
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
            'message' => "Колекція {$collectionName} є частиною зв`язку Багато-до-Багатьох.",
            'related_collections' => $usedCollections,
            'recommendation' => "Вкладення не дозволяється при такому зв`язку.",
        ];
    }
}
