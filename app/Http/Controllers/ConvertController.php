<?php

namespace App\Http\Controllers;

use App\Actions\CreateConnectionName;
use App\Http\Handlers\StepResultHandler;
use App\Http\Requests\StoreConvertRequest;
use App\Models\ConversionProgress;
use App\Models\Convert;
use App\Models\MongoSchema\MongoDatabase;
use App\Models\SQLSchema\SQLDatabase;
use App\Services\DatabaseConnections\SQLConnectionParamsProvider;
use App\Services\ConversionStepExecutor;
use App\Services\DatabaseConnections\ConnectionCreator;
use App\Services\DatabaseConnections\ConnectionTester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConvertController extends Controller
{
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
        $converts = Convert::with('user', 'sqlDatabase', 'mongoDatabase')->get();

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
        set_time_limit(10);
        try {
            // Execute the first step
            $result = $this->conversionStepExecutor->firstStep($convert, $request);
        } catch (\Exception $e) {
            return redirect()->route('converts.show', compact('convert'))->withErrors(['error' => $e->getMessage()]);
        }
        // Handle the result to determine the next step
        return StepResultHandler::handle($result);
    }

    /**
     * Display the specified resource.
     */
    public function show(Convert $convert)
    {
        $convert->load(['sqlDatabase', 'mongoDatabase', 'progresses']);

        return view('convert.show', compact('convert'));
    }

    /**
     * Resume (continue) configuring after interruption
     */
    public function resume(Convert $convert)
    {
        $lastStep = $convert->lastProgress($convert);

        if ($lastStep->canContinue()) {
            return redirect()->route('convert.step.show', ['convert' => $convert, 'step' => 'adjust_datatypes']);
        } else {
            return redirect()->route('converts.show', ['convert' => $convert])->withErrors(['error' => "Can't resume this step"]);
        }
    }

    public function showStep(Request $request, Convert $convert, string $step)
    {
        $steps = config('convert_steps');

        $view = $steps[$step]['view'] ?? null;
        if ($view) {
            return view($view, array_merge(compact('convert'), $request->all()));
        }

        dd('showStep no step view');
        return redirect()->route('converts.index');
    }

    public function storeStep(Request $request, Convert $convert, string $step)
    {
        try {
            // Валідація та збереження даних для кроку $step
            return $this->conversionStepExecutor
                ->executeStep($convert, $request, $step);
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Convert $convert)
    {
        $convert->delete();

        return redirect()->route('converts.index');
    }

    // /**
    //  * Show the form for editing the specified resource.
    //  */
    // public function edit(string $id)
    // {
    //     // 
    // }

    // /**
    //  * Update the specified resource in storage.
    //  */
    // public function update(Request $request, string $id)
    // {
    //     //
    // }
}
