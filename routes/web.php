<?php

use App\Enums\MongoRelationType;
use App\Http\Controllers\ConvertController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RelationshipController;
use App\Models\Convert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

use App\Http\Controllers\TestEtlController;
use App\Models\ConversionProgress;
use App\Models\MongoSchema\Collection;
use App\Models\MongoSchema\LinkEmbedd;
use App\Services\Relationships\CollectionRelationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

Route::get('language/{locale}', function ($locale) {

    if (isset($locale) && in_array($locale, config('app.available_locales'))) {
        app()->setLocale($locale);
        Session::put('locale', $locale);
    }

    return back();
})->name('lang');

Route::get('/', function () {
    return redirect()->route('login');
})->name('welcome');

Route::get('/dashboard', function () {
    return redirect()->route('converts.index');
})->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::resource('converts', ConvertController::class)->except([
        'edit',
        'update',
    ]);

    Route::prefix('/converts/{convert}')->group(function () {

        Route::get('/resume', [ConvertController::class, 'resume'])->name('convert.resume');

        Route::middleware(['check.step.access'])->group(function () {
            Route::get('/steps/{step}', [ConvertController::class, 'showStep'])->name('convert.step.show');
            Route::post('/steps/{step}', [ConvertController::class, 'storeStep'])->name('convert.step.store');
        });

        Route::get('/process/read-schema', [ConvertController::class, 'processReadSchema'])->name('convert.process_read_schema');
        Route::get('/process/relationships', [ConvertController::class, 'processRelationships'])->name('convert.process_relationships');
        Route::get('/process/etl', [ConvertController::class, 'processEtl'])->name('convert.process_etl');

        Route::patch('/relationships', [RelationshipController::class, 'edit'])->name('convert.relationships.edit')->middleware(['check.edit.relationship.access']);
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';

// FOR TESTING ONLY------------------

Route::get('/etl', [TestEtlController::class, 'test']);
Route::get('/etlo', [TestEtlController::class, 'testOrdinary']);

Route::get('/delete', function (Request $request) {
    $id = $request->input('id');
    $convert = Convert::find($id);

    $convert->sqlDatabase->delete();
    $convert->mongoDatabase->delete();
    $convert->delete();

    return 'deleted';
});

Route::get('/delete-data', function (Request $request) {

    $id = $request->input('id');
    $convert = Convert::find($id);

    $sqlDatabase = $convert->sqlDatabase;
    $sqlDatabase->circularRefs()->delete();
    $sqlDatabase->tables()->delete();

    $mongoDatabase = $convert->mongoDatabase;
    $mongoDatabase->collections()->delete();
    return 'data deleted';
});

Route::get('/delete-all', function (Request $request) {
    DB::table('converts')->truncate();
    DB::table('sql_databases')->truncate();
    DB::table('mongo_databases')->truncate();
    DB::table('id_mappings')->truncate();
    DB::table('links_embedds')->truncate();
    DB::table('many_to_many_links')->truncate();
    DB::table('foreign_keys')->truncate();
    DB::table('fields')->truncate();
    // DB::table('embeddings')->truncate();
    // DB::table('links')->truncate();
    DB::table('conversion_progresses')->truncate();
    DB::table('columns')->truncate();
    DB::table('collections')->truncate();
    DB::table('circular_refs')->truncate();

    return 'all converts deleted';
});

Route::get('/clear-step4', function (Request $request) {

    $id = $request->input('id');
    $convert = Convert::find($id);

    $mongoDatabase = $convert->mongoDatabase()
        ->with(['collections'])
        ->first();

    $collections = $mongoDatabase->collections()->pluck('id');

    DB::table('links_embedds')
        ->whereIn('pk_collection_id', $collections)
        ->orWhereIn('fk_collection_id', $collections)
        ->delete();

    DB::table('many_to_many_links')
        ->whereIn('collection1_id', $collections)
        ->orWhereIn('collection2_id', $collections)
        ->orWhereIn('pivot_collection_id', $collections)
        ->delete();

    dd(DB::table('links_embedds')->count(), DB::table('many_to_many_links')->count());
    return 'done';
});

Route::get('/reset', function (Request $request) {
    $id = $request->input('id');
    $convert = Convert::find($id);

    $convert->updateStatus(Convert::STATUSES['CONFIGURING']);

    $convert->lastProgress()->delete(); //delete etl

    $progress = $convert->lastProgress(); // set previous as configuring
    $progress->status = ConversionProgress::STATUSES['CONFIGURING'];
    $progress->save();

    return 'done';
});

Route::get('/id-map-del', function () {
    DB::table('id_mappings')->truncate();
    return 'done';
});

Route::get('/em', function () {

    $convert = Convert::find(6);

    $mongoId = $convert->mongo_database_id;

    // $emb = $service->checkEmbeddings($collection->id, 'mainInRelated')[0]; // 'relatedInMain'

    // $emb2 = $service->checkEmbeddings($emb->fk_collection_id, 'mainInRelated')[0]; // 'relatedInMain'

    // $emb3 = $service->checkEmbeddings($emb2->fk_collection_id, 'mainInRelated'); // 'relatedInMain'

    // dd($emb3);

    // dd(Collection::find($emb3->fk_collection_id)->name, Collection::find($emb3->pk_collection_id)->name);

    // $emb0 = $service->checkEmbedding($collection->id, 'mainInRelated')[0];

    // $emb01 = $service->checkToEmbeddings($emb0->pk_collection_id, 'mainInRelated')[0];

    // $emb02 = $service->checkToEmbeddings($emb01->pk_collection_id, 'mainInRelated');

    // dd($emb02);
    // dd(Collection::find($emb01->fk_collection_id)->name, Collection::find($emb01->pk_collection_id)->name);

    function findDepth($service, $emb, &$visited, &$depth, $currentDepth = 1)
    {
        Log::info("currentDepth = $currentDepth");

        if ($depth >= 6) return false;

        foreach ($emb as $e) {
            // Перевірка вкладення
            Log::info("id = $e->id; pk = $e->pk_collection_id; fk = $e->fk_collection_id");
            if (in_array($e->id, $visited)) {
                Log::info("visited");
                continue;
            }

            $visited[] = $e->id;

            $emb1 = $service->checkEmbeddingChain($e->pk_collection_id, $e->fk_collection_id, $visited);
            Log::warning($emb1);
            // dd($emb1);

            if ((!$emb1->isEmpty()) && (!findDepth($service, $emb1, $visited, $depth, $currentDepth + 1))) {
                continue;
            }
        }

        Log::info("--\n");
        $depth = max($depth, $currentDepth); // Оновлення глибини

        if ($depth >= 6) return false;
        
        return true; // Глибина в межах допустимої
    }

    $collection = Collection::where('name', 'related4')->where('mongo_database_id', $mongoId)->first();

    $service = new CollectionRelationService();

    $emb = LinkEmbedd::where('relation_type', MongoRelationType::EMBEDDING->value)
        ->where('fk_collection_id', $collection->id)
        ->orWhere('pk_collection_id', $collection->id)
        ->get();

    // dd($emb);
    
    // $count = $emb->count();

    // $num = $count > 0 ? 1 : 0;

    // if ($count > 1) {
    //     $count2 = $emb->where('fk_collection_id', $collection->id)->count();
    //     if ($count2 !== $count) {
    //         $num = 2;
    //     }
    // }
    // dd($count, $num);

    // $depth = $num;

    $depth = 0;
    $visited = [];
    $result = findDepth($service, $emb, $visited, $depth, $depth);

    dd((string) $result, $depth);
});
