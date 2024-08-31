<?php

namespace App\Jobs;

use App\Actions\CompleteSchemaReadingStep;
use App\Events\PackageSent;
use App\Events\ReadSchema as ReadSchemaEvent;
use App\Models\Convert;
use App\Models\ConversionProgress;
use App\Models\User;
use App\Schema\SQL\Mapper;
use App\Schema\SQL\Reader;
use App\Services\DatabaseConnections\ConnectionCreator;
use App\Services\ConversionService;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ReadSchema implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The job may be attempted only once
     *
     * @var int
     */
    public $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user,
        public Convert $convert,
        public string $step,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $sqlDatabase = $this->convert->sqlDatabase;
        try {
            $connection = ConnectionCreator::create($sqlDatabase);

            $reader = new Reader($connection->getSchemaBuilder());
            $mapper = new Mapper($sqlDatabase, $reader);

            $mapper->mapSchema($sqlDatabase);

            CompleteSchemaReadingStep::execute($this->convert, $this->step);
            // ConversionService::updateConversionProgress(
            //     $this->convert,
            //     // config('convert_steps.read_schema.key'),
            //     $this->step,
            //     ConversionProgress::STATUSES['COMPLETED'],
            //     'Relational database schema has been analyzed.'
            // );

            // ConversionService::createConversionProgress(
            //     $this->convert,
            //     config("convert_steps.{$this->step}.next"),
            //     ConversionProgress::STATUSES['CONFIGURING'],
            //     'Step is configuring'
            // );


            // Трансляція події про успішне завершення
            ReadSchemaEvent::dispatch(
                $this->user->id,
                $this->convert->id,
                ReadSchemaEvent::STATUSES['COMPLETED']
            );

            // Log::info("ReadSchema job finished");
        } catch (\Throwable $e) {
            Log::error('Error processing ReadSchema job: ' . $e->getMessage(), ['exception' => $e]);

            $this->fail($e);
        }
    }

    /**
     * Handle a job failure.
    
     * @param  \Throwable  $e
     * 
     * @return void
     */
    public function failed(?\Throwable $e): void
    {
        $this->convert->clearData();

        $errorMessage = $e instanceof \App\Schema\DataTypes\UnsupportedDataTypeException
            ? 'The data type of the relational database column is not supported.'
            : 'Error: ' . ($e ? $e->getMessage() : 'Unknown error.');

        ConversionService::failConvert($this->convert, $this->step, $errorMessage);

        // Трансляція події про помилку при виконанні завдання
        ReadSchemaEvent::dispatch(
            $this->user->id,
            $this->convert->id,
            ReadSchemaEvent::STATUSES['FAILED']
        );
    }
}
