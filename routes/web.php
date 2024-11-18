<?php

use App\Http\Controllers\ConvertController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RelationshipController;
use App\Models\Convert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

use App\Http\Controllers\TestEtlController;
use App\Models\ConversionProgress;
use App\Models\MongoSchema\LinkEmbedd;
use App\Models\MongoSchema\ManyToManyLink;
use Illuminate\Support\Facades\DB;

use App\Services\DatabaseConnections\ConnectionCreator;

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
