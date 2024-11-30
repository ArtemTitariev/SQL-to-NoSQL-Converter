<?php

namespace App\Services\ConversionStrategies;

use App\Enums\RelationType;
use App\Http\Validators\TableColumnValidator;
use App\Models\Convert;
use App\Models\MongoSchema\Collection;
use App\Models\MongoSchema\Field;
use App\Models\SQLSchema\CircularRef;
use App\Models\SQLSchema\ForeignKey;
use App\Models\SQLSchema\SQLDatabase;
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
        $validated = TableColumnValidator::validate($request);
        $sqlDatabase = $convert->sqlDatabase()->with(['tables'])->first();
        $mongoDatabase = $convert->mongoDatabase;

        $validationResult = $this->validate($validated, $sqlDatabase);

        if (! is_null($validationResult)) {
            return $validationResult;
        }

        $tables = $validated['tables'];
        $columns = $validated['columns'];
        $break = $validated['break_relations'] === static::BREAK_RELATIONS['BREAK'];

        // перевірка на зв'язки
        $missingTables = $this->getMissingTables($tables, $sqlDatabase);
        if (!empty($missingTables)) {

            if (! $break) {
                return new StrategyResult(
                    result: StrategyResult::STATUSES['REDIRECT'],
                    // details: 'Вибрані не всі необхідні таблиці',
                    with: [
                        'missingTables' => $missingTables,
                        'message' => __('validation.required_tables'),
                    ],
                );
            }

            $tables = $this->removeUnnecessaryPivotTables($tables, $sqlDatabase);

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
        }

        $tablesCollection = $sqlDatabase->tables()->whereIn('name', $tables)->get();

        DB::transaction(function () use ($mongoDatabase, $tablesCollection, $columns) {
            foreach ($tablesCollection as $table) {
                $collection = Collection::create([
                    'mongo_database_id' => $mongoDatabase->id,
                    'name' => $table->name,
                    'sql_table_id' => $table->id,
                ]);

                foreach ($columns[$table->name] as $column => $type) {
                    Field::create([
                        'collection_id' => $collection->id,
                        'name' => $column,
                        'type' => $type,
                    ]);
                }
            }
        });

        // Return success response
        return new StrategyResult(
            result: StrategyResult::STATUSES['COMPLETED'],
            details: 'Selected tables and data types are saved.',
            next: config('convert_steps.adjust_datatypes.next'),
        );
    }

    private function validate(array &$data, SQLDatabase $sqlDatabase)
    {
        $tables = $data['tables']; // ['users', 'phones', 'roles', ...]
        $columns = $data['columns']; // ['users' => ['id' => 'int', 'name' => 'string', ...], 'phones' => [...], ...]

        $sqlTables = $sqlDatabase->tables()->with(['columns'])->get();

        // Масив назв таблиць для перевірки
        $sqlTableNames = $sqlTables->pluck('name')->toArray();

        // Перевірка чи не додав користувач зайвих таблиць
        if (!empty(array_diff($tables, $sqlTableNames))) {
            throw ValidationException::withMessages(['tables' => __('validation.superfluous_tables')]);
        }

        // Асоціативний масив для таблиць і стовпців
        $tableMap = $sqlTables->keyBy('name');

        // Перевірка чи не додав користувач зайвих типів даних 
        foreach ($tables as $table) {
            $sqlTable = $tableMap[$table] ?? null;

            if (!$sqlTable) {
                continue; // Якщо таблиця не знайдена, пропустити
            }

            $sqlColumns = $sqlTable->columns->keyBy('name');

            // Перевірити чи нема лишніх стовпців
            $firstArrayKeys = array_keys($columns[$table]);
            $secondArrayNames = $sqlColumns->keys()->toArray();

            // Перевіряємо, чи всі ключі з першого масиву присутні у другому масиві
            if (array_diff($firstArrayKeys, $secondArrayNames)) {
                throw ValidationException::withMessages(['columns' => __('validation.superfluous_columns')]);
            }

            foreach ($columns[$table] as $column => $type) {
                $sqlColumn = $sqlColumns[$column] ?? null;

                if (!$sqlColumn) {
                    throw ValidationException::withMessages([
                        'columns' =>  __(
                            'validation.superfluous_column',
                            ['column' => $column, 'table' => $table]
                        )
                    ]);
                }

                if (!in_array($type, $sqlColumn->convertable_types)) {
                    throw ValidationException::withMessages([
                        'columns' => __('validation.superfluous_datatypes')
                    ]);
                }
            }
        }

        return null;
    }

    private function getMissingTables($tables, SQLDatabase $sqlDatabase)
    {
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

        return array_values(array_diff($neededTables, $tables));
    }

    private function removeUnnecessaryPivotTables(array $tables, SQLDatabase $sqlDatabase): array
    {
        // Якщо при зв'язку N-N користувач не обирає якусь із "основних" таблиць,
        // то pivot таблиця також видаляється (разом зі зв'язками).
        // Наприклад, для posts, tags, post_tag. Користувач не обрав posts. Залишається тільки tags.
        // Користувач не обрав posts і tags. Всі три видаляються.
        $pivotTables = DB::table('tables')
            ->join('foreign_keys', 'foreign_keys.table_id', '=', 'tables.id')
            ->where('foreign_keys.relation_type', RelationType::MANY_TO_MANY)
            ->whereIn('tables.name', $tables)
            ->where('tables.sql_database_id', $sqlDatabase->id)
            ->select(['tables.name', 'foreign_keys.foreign_table'])
            ->get()
            ->toArray();

        // Перевіряємо кожен елемент у pivotTables
        foreach ($pivotTables as $pivot) {
            // Якщо foreign_table відсутній у $tables
            if (!in_array($pivot->foreign_table, $tables)) {
                // Видаляємо таблицю з $tables за ключем name
                $key = array_search($pivot->name, $tables);
                if ($key !== false) {
                    unset($tables[$key]);
                }
            }
        }

        return array_values($tables);
    }
}
