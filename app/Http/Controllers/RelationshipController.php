<?php

namespace App\Http\Controllers;

use App\Actions\CreateConnectionName;
use App\Http\Handlers\StepResultHandler;
use App\Http\Requests\StoreConvertRequest;
use App\Models\ConversionProgress;
use App\Models\Convert;
use App\Models\MongoSchema\LinkEmbedd;
use App\Models\MongoSchema\ManyToManyLink;
use App\Models\MongoSchema\MongoDatabase;
use App\Models\SQLSchema\SQLDatabase;
use App\Services\DatabaseConnections\SQLConnectionParamsProvider;
use App\Services\ConversionStepExecutor;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RelationshipController extends Controller
{

    public function edit(Request $request, Convert $convert)
    {
        sleep(4);

        // Валідація вхідних даних
        $validator = Validator::make($request->all(), [
            'relationData' => 'required|string',
            'RelationTypeLinkEmbedd' => 'nullable|string',
            'RelationTypeManyToMany' => 'nullable|string',
        ]);

        // Якщо валідація не проходить
        if ($validator->fails()) {
            return response()->json([
                'message' => __('Validation failed'),
                'errors' => $validator->errors()
            ], 422); // Код 422 означає Unprocessable Entity (помилка валідації)
        }

        try {
            $data = json_decode(decrypt($request->input('relationData') . 'fsdf'), true);
        } catch (DecryptException $e) {
            // return redirect()->back()->withErrors(['expection' => $e->getMessage()]);
            return response()->json([
                'message' => __('An error occurred while updating relationship'),
                'errors' =>
                [
                    'decrypt' => [$e->getMessage()],
                ],
            ], 500);
        }



        return response()->json([
            'message' => __('Changes saved successfully!'),
            'relationship' => $data,
        ], 200);




        // $relation = $data['model']::findOrFail($data['id']);

        // if ($relation instanceof LinkEmbedd) {
        //     // Обробка LinkEmbedd
        // } elseif ($relation instanceof ManyToManyLink) {
        //     // Обробка ManyToManyLink
        // }

        // dd($request->all(), $data, $relation);
    }
}
