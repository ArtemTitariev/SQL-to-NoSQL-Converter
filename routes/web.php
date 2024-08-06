<?php

use App\Http\Controllers\ConvertController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

use Illuminate\Support\Facades\DB;
use App\Schema\SQL\Reader;
use App\Schema\SQL\Mapper;
use App\Models\SQLSchema\SQLDatabase;
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


Route::get('/read', function() {

    $database = SQLDatabase::find(3);

    $connection = ConnectionCreator::create($database);
    
    $reader = new Reader($connection->getSchemaBuilder());
    $mapper = new Mapper($reader);

    
    $mapper->mapSchema($database);
});