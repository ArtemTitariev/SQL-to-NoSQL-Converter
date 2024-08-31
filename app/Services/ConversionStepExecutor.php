<?php

namespace App\Services;

use App\Actions\CreateConnectionName;
use App\Models\Convert;
use App\Models\ConversionProgress;
use App\Services\ConversionStrategies\ConversionStrategyInterface;
use Illuminate\Http\Request;

class ConversionStepExecutor
{
    /**
     * Array of 
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
        return $this->executeStep($convert, $request, array_key_first($this->steps), [
            'createConnectionName' => new CreateConnectionName(),
        ]);
    }

    public function executeStep(Convert $convert, Request $request, string $step, array $data = [])
    {
        if (! isset($this->strategies[$step])) {
            abort(403); //--------------------------------
        }

        if (($this->steps[$step]['number'] !== 1) && (! $this->steps[$step]['is_manual'])) {
            // Створити запис про початок виконання кроку
            ConversionService::createConversionProgress($convert, $step, ConversionProgress::STATUSES['IN_PROGRESS'], 'Step is in progress');
        }

        try {
            $result = $this->strategies[$step]->execute($convert, $request, $data);
            // Якщо "штатна" помилка
            if ($result->isFailed()) {
                // Перехід в контролер
                return $result;
            }
            // якщо крок виконаний одразу
            if ($result->isCompleted()) {
                ConversionService::updateConversionProgress($convert, $step, ConversionProgress::STATUSES['COMPLETED'], $result->getDetails());
            }
        } catch (\App\Schema\DataTypes\UnsupportedDataTypeException $e) {
            ConversionService::failConvert($convert, $step, 'The data type of the relational database column is not supported.');
            throw $e;
        } catch (\Exception $e) {
            ConversionService::failConvert($convert, $step, 'Error: ' . $e->getMessage());
            throw $e;
        }
        if ($result->isProcessing()) {
            // dd($result->getView());
            ConversionService::updateConversionProgress($convert, $step, ConversionProgress::STATUSES['IN_PROGRESS'], $result->getDetails());
            return view($result->getView())->with($result->getWith() ?? []);
        }

        $nextStep = $this->steps[$step]['next'] ?? null;

        if ($nextStep !== null) {
            if ($this->steps[$nextStep]['is_manual'] ?? false) {
                ConversionService::createConversionProgress($convert, $nextStep, ConversionProgress::STATUSES['CONFIGURING'], 'Configuring step');

                return redirect()->route('convert.step.show', ['convert' => $convert, 'step' => $nextStep]);
            } else {
                return $this->executeStep($convert, $request, $nextStep, $data);
            }
        }

        $convert->update(['status' => Convert::STATUSES['COMPLETED']]);

        dd('convert.complete reached');
        return redirect()->route('convert.complete', $convert);
    }
}
