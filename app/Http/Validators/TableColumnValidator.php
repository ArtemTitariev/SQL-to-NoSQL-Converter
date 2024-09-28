<?php

namespace App\Http\Validators;

use App\Services\ConversionStrategies\AdjustDatatypesStrategy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TableColumnValidator
{
    /**
     * @throws Illuminate\Validation\ValidationException
     */
    public static function validate(Request $request)
    {
        // dd($request->input('tables', []));
        $validator = Validator::make([
            'tables' => $request->input('tables', []), // Якщо таблиці не передані, підставляємо порожній масив
            'columns' => $request->input('columns', []), // Аналогічно для стовпців
            'break_relations' => $request->input('break_relations')
        ], [
            'break_relations' => ['bail', 'required', 'string', function ($attribute, $value, $fail) {
                if (! in_array($value, AdjustDatatypesStrategy::BREAK_RELATIONS)) {
                    $fail(__('Stop breaking everything!'));
                }
            }],
            'tables' => 'required|array',
            'columns' => [
                'bail',
                'required',
                'array',
                function ($attribute, $value, $fail) use ($request) {
                    // Масив таблиць з форми
                    $tables = $request->tables;

                    // // Перевіряємо, чи всі таблиці з масиву columns є в масиві tables
                    // foreach ($value as $table => $columns) {
                    //     if (!in_array($table, $tables)) {
                    //         $fail("Таблиця '{$table}' не входить до вибраних таблиць.");
                    //     }
                    // }

                    // Перевіряємо, чи всі таблиці з масиву tables є в масиві columns
                    foreach ($tables as $table) {
                        if (!array_key_exists($table, $value)) {
                            $fail(__('validation.required_table_in_columns', ['table' => $table]));
                        }
                    }
                }
            ],
        ], [
            'tables.required' => __('validation.required_table'),
            'columns.required' => __('validation.required_columns'),
        ]);

        $validator->stopOnFirstFailure()->validate();

        return $validator->validated();
    }
}
