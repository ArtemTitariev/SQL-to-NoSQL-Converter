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
            $this->createConversionProgress($convert, $step, ConversionProgress::STATUSES['IN_PROGRESS'], 'Step is in progress');
        }

        try {
            $result = $this->strategies[$step]->execute($convert, $request, $data);
            // Якщо "штатна" помилка
            if ($this->isFailureResult($result)) {
                // Перехід в контролер
                return $result;
            }
            $this->updateConversionProgress($convert, $step, ConversionProgress::STATUSES['COMPLETED'], $result['details']);
        } catch (\App\Schema\DataTypes\UnsupportedDataTypeException $e) {
            $this->failConvert($convert, $step, 'The data type of the relational database column is not supported.');
            throw $e;
        } catch (\Exception $e) {
            $this->failConvert($convert, $step, 'Error: ' . $e->getMessage());
            throw $e;
        }

        $nextStep = $this->steps[$step]['next'] ?? null;

        if ($nextStep !== null) {
            if ($this->steps[$nextStep]['is_manual'] ?? false) {
                $this->createConversionProgress($convert, $nextStep, ConversionProgress::STATUSES['CONFIGURING'], 'Configuring step');

                return redirect()->route('convert.step.show', ['convert' => $convert, 'step' => $nextStep]);
            } else {
                return $this->executeStep($convert, $request, $nextStep, $data);
            }
        }

        $convert->update(['status' => Convert::STATUSES['COMPLETED']]);

        dd('convert.complete reached');
        return redirect()->route('convert.complete', $convert);
    }

    /**
     * Chech if result with error
     *
     * @param mixed $result
     * @return bool
     */
    private function isFailureResult($result)
    {
        return is_array($result) && $result['status'] === 'failed';
    }

    /**
     * Fail current profress and whole convert
     *
     * @param \App\Models\Convert $convert
     * @param string $step
     * @param string $message
     * @return void
     * @throws \Exception
     */
    private function failConvert($convert, $step, $message)
    {
        $this->updateConversionProgress(
            $convert,
            $step,
            ConversionProgress::STATUSES['ERROR'],
            $message
        );

        $convert->fail();
    }

    /**
     * Crate new conversion progress
     *
     * @param \App\Models\Convert $convert
     * @param string $step
     * @param string $status
     * @param string $dateils
     * @return void
     */
    protected function createConversionProgress(Convert $convert, string $step, string $status, string $details)
    {
        ConversionProgress::create([
            'convert_id' => $convert->id,
            'step' => $this->steps[$step]['number'],
            'name' => $this->steps[$step]['name'],
            'status' => $status,
            'details' => $details,
        ]);

        $convert->touch();
    }

    /**
     * Update or crate conversion progress
     *
     * @param \App\Models\Convert $convert
     * @param string $step
     * @param string $status
     * @param string $dateils
     * @return void
     */
    protected function updateConversionProgress(Convert $convert, string $step, string $status, string $details)
    {
        ConversionProgress::updateOrCreate(
            [
                'convert_id' => $convert->id,
                'step' => $this->steps[$step]['number'],
                'name' => $this->steps[$step]['name'],
            ],
            [
                'status' => $status,
                'details' => $details,
            ]
        );

        $convert->touch();
    }
}
