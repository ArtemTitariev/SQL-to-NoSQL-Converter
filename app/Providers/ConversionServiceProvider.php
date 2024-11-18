<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ConversionStepExecutor;
use App\Services\ConversionStrategies\AdjustDatatypesStrategy;
use App\Services\ConversionStrategies\AdjustRelationshipsStrategy;
use App\Services\ConversionStrategies\EtlStrategy;
use App\Services\ConversionStrategies\InitializeConversionStrategy;
use App\Services\ConversionStrategies\ProcessRelationshipsStrategy;
use App\Services\ConversionStrategies\ReadSchemaStrategy;

class ConversionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ConversionStepExecutor::class, function ($app) {
            $strategies = [
                'initialize_conversion' => $app->make(InitializeConversionStrategy::class),
                'read_schema' => $app->make(ReadSchemaStrategy::class),
                'adjust_datatypes' => $app->make(AdjustDatatypesStrategy::class),
                'process_relationships' => $app->make(ProcessRelationshipsStrategy::class),
                'adjust_relationships' => $app->make(AdjustRelationshipsStrategy::class),
                'etl' => $app->make(EtlStrategy::class),
                // Map other steps to their strategies...
            ];
            
            return new ConversionStepExecutor($strategies);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
