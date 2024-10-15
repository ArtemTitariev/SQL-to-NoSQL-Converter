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

            if ($relation->relation_type->value === $validated['relationTypeLinkEmbedd']) {
                return $this->successResponse($decodedRelation);
            }

            switch ($relationType) {
                case MongoRelationType::LINKING:  // Зміна Embedding на Linking
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
                    // Зміна Link на Embedding

                    // Заборонено:
                    // •    Якщо є self ref
                    // •    Якщо це частина complex зв'язку.
                    // •    Вкладати ту колекцію, яка в круговому з’єднанні. (це включає перевірку наступного пункту, тож можна кругові залежності не перевіряти)
                    // •    Вкладати ту колекцію, яка пов’язана як linking з іншими (якщо в неї є вкладення - то можна), але й на ту не мають посилатися ще хтось
                    // (А якщо якась іще вкладає pk_collection, яку я хочу вкласти в fk_collection - не проблема) ----------------------
                    //  Але й інші не мають бути пов’язані як linking ще з якимись. 
                    // Тобто, якщо колекція pk_colelction використовує linking, вкладати її не можна.
                    


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
