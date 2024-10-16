<?php

namespace App\Http\Controllers;

use App\Enums\MongoManyToManyRelation;
use App\Enums\MongoRelationType;
use App\Enums\RelationType;
use App\Http\Handlers\StepResultHandler;
use App\Http\Requests\UpdateRelationshipRequest;
use App\Models\ConversionProgress;
use App\Models\Convert;
use App\Models\MongoSchema\LinkEmbedd;
use App\Models\MongoSchema\ManyToManyLink;
use App\Models\MongoSchema\MongoDatabase;
use App\Models\SQLSchema\SQLDatabase;
use App\Services\ConversionStepExecutor;
// use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RelationshipController extends Controller
{

    public function edit(UpdateRelationshipRequest $request, Convert $convert)
    {
        // sleep(6);
        $validated = $request->validated();
        $decodedRelation = $request->decodedRelationData; //from "after" validation method


        $relation = $decodedRelation['model']::findOrFail($decodedRelation['id']);

        $mongoDatabase = $convert->mongoDatabase()
            ->with(['collections'])
            ->first();

        // $collections = $mongoDatabase->collections()
        //     ->with(['linksEmbeddsFrom', 'linksEmbeddsTo', 'manyToManyPivot'])
        //     ->get();

        if ($relation instanceof LinkEmbedd) {
            // Обробка LinkEmbedd
            $relationType = MongoRelationType::tryFrom($validated['relationTypeLinkEmbedd']);

            switch ($relationType) {
                case MongoRelationType::LINKING:  // Зміна Embedding на Linking

                    if ($relation->relation_type === $relationType) {
                        // return $this->successResponse($decodedRelation);
                        dd('nothing changed');
                        return response()->json([
                            'status' => 'nothing changed',
                        ], 409);
                    }

                    // Заборонено:
                    // •    при вкладеннях

                    // перевірити, чи не вкладена посилальна pk_collection (окрім як в fk_collection)
                    // $links = LinkEmbedd::where('relation_type', MongoRelationType::EMBEDDING->value)
                    //     ->where(function (Builder $query) use ($relation) {
                    //         $query->where(function (Builder $query) use ($relation) {
                    //             $query->where('pk_collection_id', $relation->pk_collection_id)
                    //                 ->where('fk_collection_id', '<>', $relation->fk_collection_id);
                    //         })
                    //             ->orWhere('pk_collection_id', '=', $relation->fk_collection_id);
                    //     })
                    //     ->get();


                    // Перевірка, чи не вкладеною є головна (fk_collection)
                    $mainEmbeddedTo = LinkEmbedd::where('relation_type', MongoRelationType::EMBEDDING->value)
                        ->where('pk_collection_id', '=', $relation->fk_collection_id)
                        ->get();

                    if ($mainEmbeddedTo->isNotEmpty()) {

                        $embeddedTo = $mainEmbeddedTo->map(fn($embedd) => [
                            'id' => $embedd->fkCollection->id,
                            'name' => $embedd->fkCollection->name,
                        ]);

                        // $message = "Колекція {$relation->fkCollection()->pluck('name')[0]} є вкладеною (Embedding) в " .
                        //     "{$mainEmbeddedTo[0]->pkCollection()->pluck('name')[0]}";

                        return response()->json([
                            'status' => 'error',
                            'type' => 'main_collection_is_embedded',
                            'message' => "Колекція {$relation->fkCollection()->value('name')} є вкладеною.",
                            'embedded_to' => $embeddedTo,
                            'recommendation' => 'Спочатку змініть зазначені зв`язки на посилання (Linking).',
                        ], 409);
                    }

                    // Перевірка, чи не вкладена посилальна (pk_collection) ще кудись, крім головної ?????----------------------------------
                    // За реалізованою логікою така ситуація (коли одна колекція вкладена в дві інші) не має статись, але перевірити можна.
                    // Тоди виходить "замкнуте коло", бо і там і там одночасто змінити embedding на link не вийде. -------------------------
                    $embeddsToRelated = LinkEmbedd::where('relation_type', MongoRelationType::EMBEDDING->value)
                        ->where('pk_collection_id', '=', $relation->pk_collection_id)
                        ->where('fk_collection_id', '<>', $relation->fk_collection_id)
                        ->get();

                    if ($embeddsToRelated->isNotEmpty()) {
                        $embeddedTo = $embeddsToRelated->map(fn($embedd) => [
                            'id' => $embedd->fkCollection->id,
                            'name' => $embedd->fkCollection->name,
                        ]);

                        return response()->json([
                            'status' => 'error',
                            'type' => 'related_collection_is_embedded',
                            'message' => "Колекція {$relation->pkCollection()->value('name')} є вкладеною.",
                            'embedded_to' => $embeddedTo,
                            'recommendation' => 'Спочатку змініть зв`язки на посилання (Linking).',
                        ], 409);
                    }


                    // SAVE CHANGES
                    // $relation->changeToLinking();
                    break;

                case MongoRelationType::EMBEDDING:
                    $embedInMain = $request->input('embedInMain'); // from 'after' method

                    if ($relation->relation_type === $relationType) {
                        if ($relation->embed_in_main === $embedInMain) {
                            dd('nothing changed');
                            // nothing changed
                            return response()->json([
                                'status' => 'nothing changed',
                            ], 409);
                            // break;

                        } else {
                            // chanche embedding direction

                            if ($embedInMain) {
                                // Було main in related, стане related in main  +++++++++++
                                // main => fk_collection
                                // related => pk_collection

                                // Заробонено:
                                // •    Якщо в related є self ref
                                // •    Якщо це частина complex зв'язку (а раптом??) 
                                // •    Якщо в related є посилання (links)
                                // •    Якщо є посилання на related

                                $pkCollection = $relation->pkCollection;

                                // check self ref in related
                                $selfRefExists = LinkEmbedd::where('fk_collection_id', $pkCollection->id)
                                    ->where('sql_relation', RelationType::SELF_REF->value)
                                    ->exists();

                                if ($selfRefExists) {
                                    return response()->json([
                                        'status' => 'error',
                                        'type' => 'self_ref',
                                        'message' => "Колекція {$pkCollection->name} має посилання на себе.",
                                        'recommendation' => 'Вкладення при такому зв`язку не підтримуються.',
                                    ], 409);
                                }
                                // check complex
                                if ($relation->sql_relation->isComplex()) {
                                    return response()->json([
                                        'status' => 'error',
                                        'type' => 'complex_relation',
                                        'message' => "Даний зв`язок є частиною складного зв`язку в колекції {$pkCollection->name}.",
                                        'recommendation' => 'Вкладення при такому зв`язку не підтримуються.',
                                    ], 409);
                                }

                                // Check links in related
                                $linksFrom = LinkEmbedd::where('fk_collection_id', $pkCollection->id)
                                    ->where('relation_type', MongoRelationType::LINKING->value)
                                    ->get();

                                if ($linksFrom->isNotEmpty()) {
                                    $linkedWith = $linksFrom->map(fn($link) => [
                                        'id' => $link->pkCollection->id,
                                        'name' => $link->pkCollection->name,
                                    ]);

                                    return response()->json([
                                        'status' => 'error',
                                        'type' => 'main_collection_has_links',
                                        'message' => "Колекція {$pkCollection->name} має посилання (Linking).",
                                        'linkend_with' => $linkedWith,
                                        'recommendation' => 'Спочатку змініть зв`язки на вкладення (Embedding).',
                                    ], 409);
                                }


                                // Check links to related
                                $linksTo = LinkEmbedd::where('pk_collection_id', $pkCollection->id)
                                    ->where('relation_type', MongoRelationType::LINKING->value)
                                    ->get();

                                if ($linksTo->isNotEmpty()) {
                                    $linkedWith = $linksTo->map(fn($link) => [
                                        'id' => $link->fkCollection->id,
                                        'name' => $link->fkCollection->name,
                                    ]);

                                    return response()->json([
                                        'status' => 'error',
                                        'type' => 'links_to_main_collection',
                                        'message' => "На колекцію {$pkCollection->name} є посилання (Linking).",
                                        'linked_from' => $linkedWith,
                                        'recommendation' => "Вкласти колекцію {$pkCollection->name} неможливо при такому зв`язку.",
                                    ], 409);
                                }

                                // Check N-N with related
                                $nn = ManyToManyLink::where('collection1_id', $pkCollection->id)
                                    ->orWhere('collection2_id', $pkCollection->id)
                                    ->orWhere('pivot_collection_id', $pkCollection->id)
                                    ->get();

                                if ($nn->isNotEmpty()) {
                                    $usedCollections = $nn->map(fn($rel) => [
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
                                        'message' => "Колекція {$pkCollection->name} є частиною звязку Багато-до-Багатьох.",
                                        'collections' => $usedCollections,
                                        'recommendation' => "Вкласти колекцію {$pkCollection->name} неможливо при такому зв`язку.",
                                    ], 409);
                                }

                                // SAVE CHANGES
                                // $relation->changeEmbeddingDirection();
                                break;
                                //--------------------------------------

                            } else {
                                // Було related in main, стане main in related  ------------
                                // main => fk_collection
                                // related => pk_collection

                                // Заробонено:
                                // •    Якщо в main є self ref
                                // •    Якщо це частина complex зв'язку (а раптом??) 
                                // •    Якщо в main є посилання (links)
                                // •    Якщо є посилання на main

                                // dd($relation);
                                $fkCollection = $relation->fkCollection;

                                // check self ref in main
                                $selfRefExists = LinkEmbedd::where('fk_collection_id', $fkCollection->id)
                                    ->where('sql_relation', RelationType::SELF_REF->value)
                                    ->exists();

                                if ($selfRefExists) {
                                    return response()->json([
                                        'status' => 'error',
                                        'type' => 'self_ref',
                                        'message' => "Колекція {$fkCollection->name} має посилання на себе.",
                                        'recommendation' => 'Вкладення при такому зв`язку не підтримуються.',
                                    ], 409);
                                }
                                // check complex
                                if ($relation->sql_relation->isComplex()) {
                                    return response()->json([
                                        'status' => 'error',
                                        'type' => 'complex_relation',
                                        'message' => "Даний зв`язок є частиною складного зв`язку в колекції {$fkCollection->name}.",
                                        'recommendation' => 'Вкладення при такому зв`язку не підтримуються.',
                                    ], 409);
                                }

                                // Check links in main
                                $linksFrom = LinkEmbedd::where('fk_collection_id', $fkCollection->id)
                                    ->where('relation_type', MongoRelationType::LINKING->value)
                                    ->get();

                                if ($linksFrom->isNotEmpty()) {
                                    $linkedWith = $linksFrom->map(fn($link) => [
                                        'id' => $link->pkCollection->id,
                                        'name' => $link->pkCollection->name,
                                    ]);

                                    return response()->json([
                                        'status' => 'error',
                                        'type' => 'main_collection_has_links',
                                        'message' => "Колекція {$fkCollection->name} має посилання (Linking).",
                                        'linkend_with' => $linkedWith,
                                        'recommendation' => 'Спочатку змініть зв`язки на вкладення (Embedding).',
                                    ], 409);
                                }

                                // Check links to main
                                $linksTo = LinkEmbedd::where('pk_collection_id', $fkCollection->id)
                                    ->where('relation_type', MongoRelationType::LINKING->value)
                                    ->get();

                                if ($linksTo->isNotEmpty()) {
                                    $linkedWith = $linksTo->map(fn($link) => [
                                        'id' => $link->fkCollection->id,
                                        'name' => $link->fkCollection->name,
                                    ]);

                                    return response()->json([
                                        'status' => 'error',
                                        'type' => 'links_to_main_collection',
                                        'message' => "На колекцію {$fkCollection->name} є посилання (Linking).",
                                        'linked_from' => $linkedWith,
                                        'recommendation' => "Вкласти колекцію {$fkCollection->name} неможливо при такому зв`язку.",
                                    ], 409);
                                }

                                // Check N-N with main
                                // Check N-N with main
                                $nn = ManyToManyLink::where('collection1_id', $fkCollection->id)
                                    ->orWhere('collection2_id', $fkCollection->id)
                                    ->orWhere('pivot_collection_id', $fkCollection->id)
                                    ->get();

                                if ($nn->isNotEmpty()) {
                                    $usedCollections = $nn->map(fn($rel) => [
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
                                        'message' => "Колекція {$fkCollection->name} є частиною звязку Багато-до-Багатьох.",
                                        'collections' => $usedCollections,
                                        'recommendation' => "Вкласти колекцію {$fkCollection->name} неможливо при такому зв`язку.",
                                    ], 409);
                                }

                                // SAVE CHANGES
                                // $relation->changeEmbeddingDirection();
                                break;
                            }
                            // -----------------------------------------------------------------------------------------------
                        }
                    } else {
                        // Зміна Link на Embedding

                        // у залежності від напрямку
                        if ($embedInMain) {

                            // Якщо related in main +++++++++++
                            // main => fk_collection
                            // related => pk_collection

                            // Заробонено:
                            // •    Якщо в related є self ref
                            // •    Якщо це частина complex зв'язку (а раптом??) 
                            // •    Якщо в related є посилання (links)
                            // •    Якщо є посилання на related, окрім поточного

                            $pkCollection = $relation->pkCollection;

                            // check self ref in related
                            $selfRefExists = LinkEmbedd::where('fk_collection_id', $pkCollection->id)
                                ->where('sql_relation', RelationType::SELF_REF->value)
                                ->exists();

                            if ($selfRefExists) {
                                return response()->json([
                                    'status' => 'error',
                                    'type' => 'self_ref',
                                    'message' => "Колекція {$pkCollection->name} має посилання на себе.",
                                    'recommendation' => 'Вкладення при такому зв`язку не підтримуються.',
                                ], 409);
                            }
                            // check complex
                            if ($relation->sql_relation->isComplex()) {
                                return response()->json([
                                    'status' => 'error',
                                    'type' => 'complex_relation',
                                    'message' => "Даний зв`язок є частиною складного зв`язку в колекції {$pkCollection->name}.",
                                    'recommendation' => 'Вкладення при такому зв`язку не підтримуються.',
                                ], 409);
                            }

                            // Check links in related
                            $linksFrom = LinkEmbedd::where('fk_collection_id', $pkCollection->id)
                                ->where('relation_type', MongoRelationType::LINKING->value)
                                ->get();

                            if ($linksFrom->isNotEmpty()) {
                                $linkedWith = $linksFrom->map(fn($link) => [
                                    'id' => $link->pkCollection->id,
                                    'name' => $link->pkCollection->name,
                                ]);

                                return response()->json([
                                    'status' => 'error',
                                    'type' => 'main_collection_has_links',
                                    'message' => "Колекція {$pkCollection->name} має посилання (Linking).",
                                    'linkend_with' => $linkedWith,
                                    'recommendation' => 'Спочатку змініть зв`язки на вкладення (Embedding).',
                                ], 409);
                            }

                            // Check links to related - EXCEPT FOT CURRNT RELATIONSHIP
                            $linksTo = LinkEmbedd::where('pk_collection_id', $pkCollection->id)
                                ->where('relation_type', MongoRelationType::LINKING->value)
                                ->where('id', '<>', $relation->id)
                                ->get();

                            if ($linksTo->isNotEmpty()) {
                                $linkedWith = $linksTo->map(fn($link) => [
                                    'id' => $link->fkCollection->id,
                                    'name' => $link->fkCollection->name,
                                ]);

                                return response()->json([
                                    'status' => 'error',
                                    'type' => 'links_to_main_collection',
                                    'message' => "На колекцію {$pkCollection->name} є посилання (Linking).",
                                    'linked_from' => $linkedWith,
                                    'recommendation' => "Вкласти колекцію {$pkCollection->name} неможливо при такому зв`язку.",
                                ], 409);
                            }

                            // Check N-N with related
                            $nn = ManyToManyLink::where('collection1_id', $pkCollection->id)
                                ->orWhere('collection2_id', $pkCollection->id)
                                ->orWhere('pivot_collection_id', $pkCollection->id)
                                ->get();

                            if ($nn->isNotEmpty()) {
                                $usedCollections = $nn->map(fn($rel) => [
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
                                    'message' => "Колекція {$pkCollection->name} є частиною звязку Багато-до-Багатьох.",
                                    'collections' => $usedCollections,
                                    'recommendation' => "Вкласти колекцію {$pkCollection->name} неможливо при такому зв`язку.",
                                ], 409);
                            }

                            // SAVE CHANGES
                            // $relation->changeToEmbedding(true);
                            break;
                            // ------------------------------------------
                        } else {
                            // Якщо main in related ------------
                            // main => fk_collection
                            // related => pk_collection

                            // Заробонено:
                            // •    Якщо в main є self ref
                            // •    Якщо це частина complex зв'язку (а раптом??) 
                            // •    Якщо в main є посилання (links), окрім як на related (поточне)
                            // •    Якщо є посилання на main

                            $fkCollection = $relation->fkCollection;

                            // check self ref in main
                            $selfRefExists = LinkEmbedd::where('fk_collection_id', $fkCollection->id)
                                ->where('sql_relation', RelationType::SELF_REF->value)
                                ->exists();

                            if ($selfRefExists) {
                                return response()->json([
                                    'status' => 'error',
                                    'type' => 'self_ref',
                                    'message' => "Колекція {$fkCollection->name} має посилання на себе.",
                                    'recommendation' => 'Вкладення при такому зв`язку не підтримуються.',
                                ], 409);
                            }
                            // check complex
                            if ($relation->sql_relation->isComplex()) {
                                return response()->json([
                                    'status' => 'error',
                                    'type' => 'complex_relation',
                                    'message' => "Даний зв`язок є частиною складного зв`язку в колекції {$fkCollection->name}.",
                                    'recommendation' => 'Вкладення при такому зв`язку не підтримуються.',
                                ], 409);
                            }

                            // Check links in main EXCEPT FOR CURRENT
                            $linksFrom = LinkEmbedd::where('fk_collection_id', $fkCollection->id)
                                ->where('relation_type', MongoRelationType::LINKING->value)
                                ->where('id', '<>', $relation->id)
                                ->get();

                            if ($linksFrom->isNotEmpty()) {
                                $linkedWith = $linksFrom->map(fn($link) => [
                                    'id' => $link->pkCollection->id,
                                    'name' => $link->pkCollection->name,
                                ]);

                                return response()->json([
                                    'status' => 'error',
                                    'type' => 'main_collection_has_links',
                                    'message' => "Колекція {$fkCollection->name} має посилання (Linking).",
                                    'linkend_with' => $linkedWith,
                                    'recommendation' => 'Спочатку змініть зв`язки на вкладення (Embedding).',
                                ], 409);
                            }

                            // Check links to main
                            $linksTo = LinkEmbedd::where('pk_collection_id', $fkCollection->id)
                                ->where('relation_type', MongoRelationType::LINKING->value)
                                ->get();

                            if ($linksTo->isNotEmpty()) {
                                $linkedWith = $linksTo->map(fn($link) => [
                                    'id' => $link->fkCollection->id,
                                    'name' => $link->fkCollection->name,
                                ]);

                                return response()->json([
                                    'status' => 'error',
                                    'type' => 'links_to_main_collection',
                                    'message' => "На колекцію {$fkCollection->name} є посилання (Linking).",
                                    'linked_from' => $linkedWith,
                                    'recommendation' => "Вкласти колекцію {$fkCollection->name} неможливо при такому зв`язку.",
                                ], 409);
                            }

                            // Check N-N with main
                            $nn = ManyToManyLink::where('collection1_id', $fkCollection->id)
                                ->orWhere('collection2_id', $fkCollection->id)
                                ->orWhere('pivot_collection_id', $fkCollection->id)
                                ->get();

                            if ($nn->isNotEmpty()) {
                                $usedCollections = $nn->map(fn($rel) => [
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
                                    'message' => "Колекція {$fkCollection->name} є частиною звязку Багато-до-Багатьох.",
                                    'collections' => $usedCollections,
                                    'recommendation' => "Вкласти колекцію {$fkCollection->name} неможливо при такому зв`язку.",
                                ], 409);
                            }

                            // SAVE CHANGES
                            // $relation->changeToEmbedding(false);
                            break;
                        }
                    }











                    // check self ref
                    if ($relation->sql_relation->isSelfRef()) {
                        return response()->json([
                            'status' => 'error',
                            'type' => 'self_ref',
                            'message' => "Колекція {$relation->pkCollection()->value('name')} має посилання на себе.",
                            'recommendation' => 'Вкладення при такому зв`язку не підтримуються.',
                        ], 409);
                    }
                    // check complex
                    if ($relation->sql_relation->isComplex()) {
                        return response()->json([
                            'status' => 'error',
                            'type' => 'complex_relation',
                            'message' => "Даний зв`язок є частиною складного зв`язку в колекції {$relation->pkCollection()->value('name')}.",
                            'recommendation' => 'Вкладення при такому зв`язку не підтримуються.',
                        ], 409);
                    }

                    // перевірка, чи має свої link-и pk_collection (вкладення - можна)

                    // перевірити, чи є link-и на pk_collection



                    // SAVE CHANGES
                    // $relation->changeToEmbedding();
                    break;

                    // (1-2) if sql_relation is self ref or complex
                    // (3) if pkCollection->name is in circular ref
                    // (4.1) if (pkCollection->linskEmbeddsFrom where rel == link is not empty and // pkCollection посилається на когось
                    // (4.2) pkCollection->linskEmbeddsTo (let) where rel == link and where let.fk_collection_id != $relation->pkConnection->id  is not empty and // на pkCollection посилається хтось (окрім цього зв'язку)


                default:
                    dd('link embedd error');
                    break;
            }

            return $this->successResponse();
            // dd($request->all(), $validated, $relation, $relationType);
        } elseif ($relation instanceof ManyToManyLink) {
            // Обробка ManyToManyLink
            $relationType = MongoManyToManyRelation::tryFrom($validated['relationTypeManyToMany']);

            // не забути перевірити інші зв'язки у всіх трьох колекціях-учасниках

            // dd($request->all(), $validated, $relationType);
        } else {
            return response()->json([
                'message' => "Unsupported model: $relation.",
            ], 419);
        }

        // dd($request->all(), $validated, $relation);


        return $this->successResponse();
    }

    private function successResponse()
    {
        return response()->json([
            'status' => 'success',
            'message' => __('Changes saved successfully!'),
        ], 200);
    }
}
