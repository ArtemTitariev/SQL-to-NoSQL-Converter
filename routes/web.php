<?php

use App\Enums\RelationType;
use App\Http\Controllers\ConvertController;
use App\Http\Controllers\ProfileController;
use App\Models\Convert;
use App\Models\SQLSchema\CircularRef;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

use Illuminate\Support\Facades\DB;

Route::get('language/{locale}', function ($locale) {

    if (isset($locale) && in_array($locale, config('app.available_locales'))) {
        app()->setLocale($locale);
        Session::put('locale', $locale);
    }

    return back();
})->name('lang');

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('/dashboard', function () {
    return view('welcome');
})->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::resource('converts', ConvertController::class)->except([
        'edit',
        'update',
    ]);

    Route::get('/converts/{convert}/resume', [ConvertController::class, 'resume'])->name('convert.resume');
    Route::middleware(['check.step.access'])->group(function () {
        Route::get('/converts/{convert}/steps/{step}', [ConvertController::class, 'showStep'])->name('convert.step.show');
        Route::post('/converts/{convert}/steps/{step}', [ConvertController::class, 'storeStep'])->name('convert.step.store');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::view('/test', 'test');

require __DIR__ . '/auth.php';

// FOR TESTING ONLY------------------

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
    DB::table('links')->truncate();
    DB::table('foreign_keys')->truncate();
    DB::table('fields')->truncate();
    DB::table('embeddings')->truncate();
    DB::table('conversion_progresses')->truncate();
    DB::table('columns')->truncate();
    DB::table('collections')->truncate();
    DB::table('circular_refs')->truncate();
    DB::table('circular_refs')->truncate();

    return 'all converts deleted';
});

// Route::get('/circular', function(Request $request) {

//     $id = $request->input('id');
//     $convert = Convert::find($id);
//     $sqlDatabase = $convert->sqlDatabase;
//     // dd(CircularRef::getByAllTableNames($sqlDatabase->id, ["table1", 'table5']));
//     dd($sqlDatabase->circularRefs);

//     return 'done';
// });
