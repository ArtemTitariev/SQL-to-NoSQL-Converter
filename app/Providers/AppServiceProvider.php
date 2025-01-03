<?php

namespace App\Providers;

use Illuminate\Support\Facades;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Languages partial
        view()->composer('partials.language_switcher', function ($view) {
            // $view->with('current_locale', app()->getLocale());
            $view->with('available_locales', config('app.available_locales'));
        });

        Facades\View::composer('convert.adjust_relationships', function (View $view) {
            $view->with('mongoManyToManyRelations', \App\Enums\MongoManyToManyRelation::getValues());
            $view->with('mongoRelationTypes', \App\Enums\MongoRelationType::getValues());
            $view->with('relationTypes', \App\Enums\RelationType::getValues());
        });

        // // Load constants
        // $constants = config('constants');
        // foreach ($constants as $key => $value) {
        //     if (!defined($key)) {
        //         define($key, $value);
        //     }
        // }
    }
}
