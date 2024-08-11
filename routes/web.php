<?php

use App\Http\Controllers\ConvertController;
use App\Http\Controllers\ProfileController;
use App\Models\Convert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

use Illuminate\Support\Facades\DB;
use App\Schema\SQL\Reader;
use App\Schema\SQL\Mapper;
use App\Models\SQLSchema\SQLDatabase;
use App\Models\MongoSchema\MongoDatabase;
use App\Services\DatabaseConnections\ConnectionCreator;

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
        'edit', 'update',
    ]);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::view('/test', 'test');

require __DIR__.'/auth.php';

// FOR TESTING ONLY------------------
Route::get('/read', function(Request $request) {
    $id = $request->input('id');
    $database = SQLDatabase::find($id);
    try {
        $connection = ConnectionCreator::create($database);
        
        $reader = new Reader($connection->getSchemaBuilder());
        $mapper = new Mapper($database, $reader);

        $mapper->mapSchema($database);
    } catch (\Exception $e) {

        // clear data
        $convert = Convert::find($id);
        $sqlDatabase = $convert->sqlDatabase;
        $sqlDatabase->circularRefs()->delete();
        $sqlDatabase->tables()->delete();

        return "Error: " . $e->getMessage();
    }
    return 'done';
});

Route::get('/delete', function(Request $request) {
    $id = $request->input('id');
    $convert = Convert::find($id);

    $convert->sqlDatabase->delete();
    $convert->mongoDatabase->delete();
    $convert->delete();

    return 'deleted';
});

Route::get('/delete-data', function(Request $request) {

    $id = $request->input('id');
    $convert = Convert::find($id);

    $sqlDatabase = $convert->sqlDatabase;
    $sqlDatabase->circularRefs()->delete();
    $sqlDatabase->tables()->delete();

    $mongoDatabase = $convert->mongoDatabase;
    $mongoDatabase->collections()->delete();
    return 'data deleted';
});