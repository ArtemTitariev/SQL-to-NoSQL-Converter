<?php

namespace App\Services;

use App\Actions\CreateConnectionName;
use App\Models\Convert;
use App\Models\ConversionProgress;
use App\Services\ConversionStrategies\StrategyResult;
use Illuminate\Http\Request;

class ConversionStepExecutor
{
    /**
     * Array of strategies
     * @param array
     */
    protected $strategies;

    /**
     * Array of steps from config
     * @param array
     */
    private $steps;

    public function __construct(array $strategies)
    {
        $this->strategies = $strategies;

        $this->steps = config('convert_steps');
    }

    public function firstStep(Convert $convert, Request $request)
    {
        return $this->executeStep(
            $convert,
            $request,
            array_key_first($this->steps),
            [
                'createConnectionName' => new CreateConnectionName(),
            ]
        );
    }

    public function executeStep(
        Convert $convert,
        Request $request,
        string $step,
        array $data = []
    ) {
        if (! isset($this->strategies[$step])) {
            abort(403); // Неправильний крок
        }

        if (($this->steps[$step]['number'] !== 1) && (! $this->steps[$step]['is_manual'])) {
            // Створити запис про початок виконання кроку

            $status = ConversionProgress::STATUSES['IN_PROGRESS'];
            $message = 'Step is in progress.';
            if ($step === config('convert_steps.etl.name')) {
                $status = ConversionProgress::STATUSES['PENDING'];
                $message = 'Step in the pending process.';
            }

            ConversionService::createConversionProgress($convert, $step, $status, $message);
        }

        try {
            $result = $this->strategies[$step]->execute($convert, $request, $data);
        } catch (\App\Schema\DataTypes\UnsupportedDataTypeException $e) {
            return $this->handleStepFailure(
                $convert,
                $step,
                'The data type of the relational database column is not supported.',
                $e
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->getMessage());
        } catch (\Exception $e) {
            return $this->handleStepFailure($convert, $step, 'Error: ' . $e->getMessage(), $e);
        }

        return $this->processResult($convert, $step, $result, $request, $data);
    }

    private function processResult(
        Convert $convert,
        string $step,
        StrategyResult $result,
        Request $request,
        array $data
    ) {
        if ($result->isProcessing()) {
            ConversionService::updateConversionProgress(
                $convert,
                $step,
                ConversionProgress::STATUSES['IN_PROGRESS'],
                $result->getDetails()
            );
            return redirect()->route($result->getRoute(), ['convert' => $convert->id]);
        }

        if ($result->isRedirect()) {
            return redirect()->back()->with($result->getWith())->withInput();
        }

        if ($result->isFailed()) {
            throw new \Exception($result->getDetails());
        }

        if ($result->isCompleted()) {
            ConversionService::updateConversionProgress(
                $convert,
                $step,
                ConversionProgress::STATUSES['COMPLETED'],
                $result->getDetails()
            );
        }

        return $this->proceedToNextStep($convert, $step, $request, $data);
    }

    private function handleStepFailure(
        Convert $convert,
        string $step,
        string $message,
        \Throwable $e
    ) {
        ConversionService::failConvert($convert, $step, $message);
        throw $e;
    }

    private function proceedToNextStep(
        Convert $convert,
        string $step,
        Request $request,
        array $data
    ) {
        $nextStep = $this->steps[$step]['next'] ?? null;

        if ($nextStep !== null) {
            if ($this->steps[$nextStep]['is_manual'] ?? false) {
                ConversionService::createConversionProgress(
                    $convert,
                    $nextStep,
                    ConversionProgress::STATUSES['CONFIGURING'],
                    'Configuring step'
                );
                return redirect()->route(
                    'convert.step.show',
                    ['convert' => $convert, 'step' => $nextStep]
                );
            } else {
                return $this->executeStep($convert, $request, $nextStep, $data);
            }
        }

        $convert->update(['status' => Convert::STATUSES['COMPLETED']]);

        return redirect()->route('convert.complete', $convert);
    }
}
