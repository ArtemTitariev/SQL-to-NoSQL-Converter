<?php

namespace App\Http\Controllers;

use App\Http\Handlers\StepResultHandler;
use App\Http\Requests\UpdateRelationshipRequest;
use App\Models\ConversionProgress;
use App\Models\Convert;
use App\Models\MongoSchema\LinkEmbedd;
use App\Models\MongoSchema\ManyToManyLink;
use App\Models\MongoSchema\MongoDatabase;
use App\Models\SQLSchema\SQLDatabase;
use App\Services\ConversionStepExecutor;
use Illuminate\Support\Facades\Auth;

class RelationshipController extends Controller
{

    public function edit(UpdateRelationshipRequest $request, Convert $convert)
    {
        // sleep(6);
        $validated = $request->validated();
        $decodedData = $request->decodedRelationData; //from "after" validation method


        $relation = $decodedData['model']::findOrFail($decodedData['id']);

        if ($relation instanceof LinkEmbedd) {
            // Обробка LinkEmbedd
        } elseif ($relation instanceof ManyToManyLink) {
            // Обробка ManyToManyLink
        }

        return response()->json([
            'message' => __('Changes saved successfully!'),
            'relationship' => $decodedData,
        ], 200);

        // dd($request->all(), $data, $relation);
    }
}
