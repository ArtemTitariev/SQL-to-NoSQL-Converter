<?php

namespace App\Http\Controllers;

use App\Http\Handlers\StepResultHandler;
use App\Http\Requests\StoreConvertRequest;
use App\Models\Convert;
use App\Services\DatabaseConnections\SQLConnectionParamsProvider;
use App\Services\ConversionStepExecutor;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ConvertController extends Controller
{
    use AuthorizesRequests;

    protected $conversionStepExecutor;

    public function __construct(ConversionStepExecutor $conversionStepExecutor)
    {
        $this->conversionStepExecutor = $conversionStepExecutor;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $converts = Convert::where('user_id', auth()->id())->with('user', 'sqlDatabase', 'mongoDatabase')->get();

        return view('convert.index', compact('converts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(SQLConnectionParamsProvider $paramsProvider)
    {
        $supportedDatabases = $paramsProvider->getSupportedDatabases();
        $commonFields = $paramsProvider->getCommonConnectionParams();
        $dbSpecificFields = $paramsProvider->getSpecificConnectionParams();

        return view('convert.create', compact('supportedDatabases', 'commonFields', 'dbSpecificFields'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreConvertRequest $request)
    {
        $convert = new Convert();
        // set_time_limit(10);
        try {
            // Execute the first step
            $result = $this->conversionStepExecutor->firstStep($convert, $request);
        } catch (\Throwable $e) {
            return redirect()->route('converts.create')->withInput()->withErrors(['error' => $e->getMessage()]);
        }
        // Handle the result to determine the next step
        return StepResultHandler::handle($result);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Convert $convert)
    {
        $this->authorize('view', $convert);

        $convert->load([
            'sqlDatabase',
            'mongoDatabase',
            'progresses' => function ($query) {
                $query->oldest(); //orderBy('step', 'desc'); // або 'asc', якщо потрібне сортування за зростанням
            },
        ]);

        return view('convert.show', compact('convert'));
    }

    /**
     * Resume (continue) configuring after interruption
     */
    public function resume(Convert $convert)
    {
        $this->authorize('resume', $convert);

        $lastStep = $convert->lastProgress($convert);
        if ($lastStep->canContinue()) {
            $steps = config('convert_steps');

            $stepKey = null;
            foreach ($steps as $key => $stepData) {
                if ($stepData['name'] === $lastStep->name) {
                    $stepKey = $key;
                    break;
                }
            }

            if ($stepKey) {
                return redirect()->route('convert.step.show', ['convert' => $convert, 'step' => $stepKey]);
            }
        }

        if ($lastStep->isEtl() && ! $lastStep->isCompletedOrError()) {
            return redirect()->route(config('convert_steps.etl.route'), ['convert' => $convert]);
        }

        return redirect()->route('converts.show', ['convert' => $convert])->withErrors(['error' => "Can't resume this step"]);
    }

    public function showStep(Request $request, Convert $convert, string $step)
    {
        $this->authorize('view', $convert);
        
        $steps = config('convert_steps');

        $view = $steps[$step]['view'] ?? null;
        if ($view) {
            return view($view, array_merge(compact('convert'), $request->all()));
        }

        return redirect()->route('converts.index');
    }

    public function storeStep(Request $request, Convert $convert, string $step)
    {
        $this->authorize('executeStep', $convert);

        try {
            // Валідація та збереження даних для кроку $step
            return $this->conversionStepExecutor
                ->executeStep($convert, $request, $step);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->withErrors($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Convert $convert)
    {
        $this->authorize('delete', $convert);

        $convert->sqlDatabase()->delete();
        $convert->mongoDatabase()->delete();
        $convert->delete();

        return redirect()->route('converts.index');
    }

    public function processReadSchema(Convert $convert)
    {
        $this->authorize('process', $convert);

        return view('convert.progress.process_read_schema', compact('convert'));
    }

    public function processRelationships(Convert $convert)
    {
        $this->authorize('process', $convert);

        return view('convert.progress.process_relationships', compact('convert'));
    }

    public function processEtl(Convert $convert)
    {
        $this->authorize('process', $convert);
        
        return view('convert.progress.process_etl', compact('convert'));
    }
}
