<?php

namespace App\Services;

use App\Actions\CreateConnectionName;
use App\Models\Convert;
use App\Models\ConversionProgress;
use App\Services\ConversionStrategies\ConversionStrategyInterface;
use Illuminate\Http\Request;

class ConversionStepExecutor
{
    protected $strategies;

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
            if (is_array($result) && $result['status'] === 'failed') { // Якщо "штатна" помилка
                // Перехід в контролер
                return $result;
            }
            $this->updateConversionProgress($convert, $step, ConversionProgress::STATUSES['COMPLETED'], $result['details']);
        } catch (\Exception $e) {
            $this->updateConversionProgress($convert, $step, ConversionProgress::STATUSES['ERROR'], 'Error: ' . $e->getMessage());
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

    protected function createConversionProgress(Convert $convert, string $step, string $status, string $details)
    {
        ConversionProgress::create([
            'convert_id' => $convert->id,
            'step' => $this->steps[$step]['number'],
            'name' => $this->steps[$step]['name'],
            'status' => $status,
            'details' => $details,
        ]);
    }

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
    }
}
