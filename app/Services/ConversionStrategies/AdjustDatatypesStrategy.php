<?php

namespace App\Services\ConversionStrategies;

use App\Models\Convert;
use App\Models\MongoSchema\Collection;
use App\Models\MongoSchema\Field;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdjustDatatypesStrategy implements ConversionStrategyInterface
{
    public function execute(Convert $convert, Request $request, array $extraParams = []): StrategyResult
    {
        // Логіка для кроку збереження типів даних

        $tables = $request->tables;
        $columns = $request->columns;

        $sqlDatabase = $convert->sqlDatabase()->with(['tables'])->first();
        $sqlTables = $sqlDatabase->tables()->with(['columns'])->get();

        //Перевірка чи не додав користувач зайвих таблиць
        if (!empty(array_diff($tables, $sqlTables->map(function ($table) {
            return $table->name;
        })->toArray()))) {
            throw new \Exception("Ви вручну додали нові таблиці. Їх немає в схемі)))");
        }

        // Перевірка чи не додав користувач зайвих типів даних  --- а ще ж перевірити чи нема лишніх стовпців
        foreach ($tables as $table) {
            $sqlTable = $sqlTables[$sqlTables->search(function ($sqlTable) use ($table) {
                return $sqlTable->name === $table;
            })];

            // dd($sqlTable);

            foreach ($columns[$table] as $column => $type) {

                $sqlColumns = $sqlTable->columns;
                $sqlColumn = $sqlColumns[$sqlColumns->search(function($sqlColumn) use ($column) {
                    return $sqlColumn->name === $column;
                })];


                if (!in_array($type, $sqlColumn->convertable_types)) {
                    throw new \Exception("Ви вручну додали тип даних для стовпця таблиці. Цей тип даних не може бути використаний)))");
                }
            }
        }
        // dd('ОК');

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
            details: 'Обрані таблиці й типи даних збереено',
            next: config('convert_steps.adjust_datatypes.next'),
        );
    }
}
