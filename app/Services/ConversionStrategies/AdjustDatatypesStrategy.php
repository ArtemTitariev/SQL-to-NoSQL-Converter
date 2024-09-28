<?php

namespace App\Services\ConversionStrategies;

use App\Http\Validators\TableColumnValidator;
use App\Models\Convert;
use App\Models\MongoSchema\Collection;
use App\Models\MongoSchema\Field;
use App\Models\SQLSchema\CircularRef;
use App\Models\SQLSchema\Table;
use App\Models\SQLSchema\ForeignKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdjustDatatypesStrategy implements ConversionStrategyInterface
{
    public const BREAK_RELATIONS = [
        'BREAK' => 'break',
        'NO_BREAK' => 'no-break',
    ];

    public function execute(Convert $convert, Request $request, array $extraParams = []): StrategyResult
    {
        // Логіка для кроку збереження типів даних

        $validated = TableColumnValidator::validate($request);

        $validationResult = $this->validate($convert, $validated);

        if (! is_null($validationResult)) {
            return $validationResult;
        }

        $tables = $validated['tables'];
        $columns = $validated['columns'];

        DB::transaction(function () use ($convert, $tables, $columns) {
            $mongoDatabaseId = $convert->mongoDatabase->id;

            foreach ($tables as $table) {
                $collection = Collection::create([
                    'mongo_database_id' => $mongoDatabaseId,
                    'name' => $table,
                ]);

                foreach ($columns[$table] as $column => $type) {
                    Field::create([
                        'collection_id' => $collection->id,
                        'name' => $column,
                        'type' => $type,
                    ]);
                }
            }
        });

        // dd($request, $tables, $columns);
        // Return success response
        return new StrategyResult(
            result: StrategyResult::STATUSES['COMPLETED'],
            details: 'Selected tables and data types are saved.',
            next: config('convert_steps.adjust_datatypes.next'),
        );
    }

    private function validate($convert, array &$data)
    {
        $tables = $data['tables']; // ['users', 'phones', 'roles', ...]
        $columns = $data['columns']; // ['users' => ['id' => 'int', 'name' => 'string', ...], 'phones' => [...], ...]
        $break = $data['break_relations'] === static::BREAK_RELATIONS['BREAK']; //bool

        $sqlDatabase = $convert->sqlDatabase()->with(['tables'])->first();
        $sqlTables = $sqlDatabase->tables()->with(['columns'])->get();

        // Масив назв таблиць для перевірки
        $sqlTableNames = $sqlTables->pluck('name')->toArray();

        // Перевірка чи не додав користувач зайвих таблиць
        if (!empty(array_diff($tables, $sqlTableNames))) {
            throw ValidationException::withMessages(['tables' => __('It looks like you manually added new tables. They are not in the schema.')]);
        }

        // Асоціативний масив для таблиць і стовпців
        $tableMap = $sqlTables->keyBy('name');

        // Перевірка чи не додав користувач зайвих типів даних 
        foreach ($tables as $table) {
            $sqlTable = $tableMap[$table] ?? null;

            if (!$sqlTable) {
                continue; // Якщо таблиця не знайдена, пропустити (можливо, це потрібно обробити окремо)
            }

            $sqlColumns = $sqlTable->columns->keyBy('name');

            // Перевірити чи нема лишніх стовпців
            $firstArrayKeys = array_keys($columns[$table]);
            $secondArrayNames = $sqlColumns->keys()->toArray();

            // Перевіряємо, чи всі ключі з першого масиву присутні у другому масиві
            if (array_diff($firstArrayKeys, $secondArrayNames)) {
                throw ValidationException::withMessages(['columns' => __('It looks like you added columns manually. They are not in the schema.')]);
            }

            foreach ($columns[$table] as $column => $type) {
                $sqlColumn = $sqlColumns[$column] ?? null;

                if (!$sqlColumn) {
                    throw ValidationException::withMessages(['columns' =>  __("Column :column is not found in table :table", ['column' => $column, 'table' => $table])]);
                }

                if (!in_array($type, $sqlColumn->convertable_types)) {
                    throw ValidationException::withMessages(['columns' => __('It looks like you manually added a data type for a table column. This data type cannot be used.')]);
                }
            }
        }

        // перевірка на зв'язки
        $neededTables = DB::table('foreign_keys')
            ->select('foreign_table')
            ->whereIn('table_id', function ($query) use ($tables, $sqlDatabase) {
                $query->select('id')
                    ->from('tables')
                    ->whereIn('name', $tables)
                    ->where('sql_database_id', $sqlDatabase->id);
            })
            ->distinct()
            ->pluck('foreign_table')
            ->toArray();

        $missingTables = array_values(array_diff($neededTables, $tables));

        if (!empty($missingTables)) {

            if (! $break) {
                return new StrategyResult(
                    result: StrategyResult::STATUSES['REDIRECT'],
                    // details: 'Вибрані не всі необхідні таблиці',
                    with: [
                        'missingTables' => $missingTables,
                        'message' => __('Not all required tables are selected'),
                    ],
                );
                // throw ValidationException::withMessages(["Вибрані не всі необхідні таблиці"]);
            } else {
                // Видалити зв'язки
                $tablesCollection = $sqlDatabase->tables()->whereIn('name', $tables)->with(['foreignKeys'])->get();
                ForeignKey::whereIn('table_id', $tablesCollection->pluck('id'))
                    ->whereNotIn('foreign_table', $tables)
                    ->delete();

                // Видалити кругові залежності
                foreach ($missingTables as $table) {
                    $circularRefs = CircularRef::getByAllTableNames($sqlDatabase->id, [$table]);
                    $circularRefs->each(function ($circularRef) {
                        $circularRef->delete();
                    });
                }

                // foreach ($tablesCollection as $table) {
                //     $keys = $table->foreignKeys;
                //     foreach ($keys as $key) {
                //         if (! in_array($key->foreign_table, $tables)) {
                //             $key->delete();
                //         }
                //     }
                // }
            }
        }
        // dd('ОК');

        return null;
    }
}
