<?php

use App\Http\Controllers\ConvertController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RelationshipController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

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
