<?php

namespace App\Schema\SQL;

use Illuminate\Database\Schema\Builder;

/**
 * SQL Schema reader
 * Provide methods to "read" relational database schema.
 */
class Reader
{
    /**
     * @var Illuminate\Database\Schema\Builder
     */
    protected $builder;

    /**
     * Index types
     */
    private const INDEXES = [
        'UNIQUE' => 'unique',
        'PRIMARY' => 'primary',
    ];

    /**
     * @var Illuminate\Database\Schema\Builder $builder;
     */
    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * Get the tables that belong to the database.
     * 
     * @return array
     */
    public function getTables(): array
    {
        return $this->builder->getTables();
    }

    /**
     * Get the names of the tables that belong to the database.
     * 
     * @return array
     */
    public function getTableListing(): array
    {
        return $this->builder->getTableListing();
    }

    /**
     * Get the columns for a given table.
     * 
     * @param string $tableName
     *
     * @return array
     */
    public function getColumns(string $tableName): array
    {
        return $this->builder->getColumns($tableName);
    }

    /**
     * Get the column listing for a given table.
     * 
     * @param string $tableName
     * 
     * @return array
     */
    public function getColumnListing(string $tableName): array
    {
        return $this->builder->getColumnListing($tableName);
    }

    /**
     * Get the full data type for the given column name.
     * 
     * @param string $tableName
     * 
     * @param string $columnName
     * 
     * @return string
     */
    public function getColumnType(string $tableName, string $columnName): string
    {
        return $this->builder->getColumnType($tableName, $columnName, true);
    }

    /**
     * Get the short data type for the given column name.
     * 
     * @param string $tableName
     * 
     * @param string $columnName
     * 
     * @return string
     */
    public function getColumnTypeName(string $tableName, string $columnName): string
    {
        return $this->builder->getColumnType($tableName, $columnName, false);
    }

    /**
     * Get the primary key for a given table.
     * 
     * @param string $tableName
     * 
     * @return array | null
     */
    public function getPrimaryKey(string $tableName): array | null
    {
        $indexes = $this->getIndexes($tableName);

        $result = array_filter($indexes, function ($index) {
            return isset($index[self::INDEXES['PRIMARY']]) &&
                $index[self::INDEXES['PRIMARY']] === true;
        });

        return !empty($result) ? array_values(reset($result)['columns']) : null;
    }

    /**
     * Get the foreign keys for a given table.
     * 
     * @param string $tableName
     * 
     * @return array
     */
    public function getForeignKeys(string $tableName): array
    {
        return $this->builder->getForeignKeys($tableName);
    }

    /*
    Правила визначення зв'язків 

    1-1: Має бути унікальний індекс на всі поля цього ключа.
        Ключ може бути складеним (покривати 2 і більше полів).

    1-N: Не має унікального індексу, або унікальний індекс не покриває всі поля ключа.
        Ключ може бути складеним (покривати 2 і більше полів).
        Може бути два окремих 1-N, вони не мають бути покриті складеним унікальним індексом. 
        Також вони не мають посилатися на одну й ту саму таблицю.

    N-N: В таблиці є два і лише два foreign keys (або два primary keys, 
        які виступають як foreign keys, вони теж unique), 
        які охоплені складеним унікальним індексом 
        (для всіх стовпців цих foreign keys). Ці зв'язки посилаються на різні таблиці. 
        Ключі можуть бути як простими, складеними (покривати 2 і більше полів), 
        так і комбінація протих і складених.
        Якщо є два 1-N, які не покриті одним складеним індексом - це два окремі 1-N.

    complex: мінімум два 1-N (або ldf N-N) та інші (один або декілька), будь які типи зв'язку.
        Іншими словами, два 1-N (або ж N-N) та ще хоча б один будь-який зв'язок.
        Важливо: якщо в таблиці є два або більше ключів, які посилаються 
        на одну й ту саму таблицю, це теж вважається складеним зв'язком
        (навіть якщо це два зв'язки 1-1, які посилаються на одну і ту ж таблицю).
    */

    /**
     * Get the foreign keys for a given table with their relation types.
     * 
     * @param string $tableName
     * 
     * @return array
     */
    public function getForeignKeysWithRelationType(string $tableName): array
    {
        $foreignKeys = $this->getForeignKeys($tableName);
        $indexes = $this->getIndexes($tableName);

        // dd($foreignKeys, $indexes);
        // Create an associative array to quickly look up unique indexes
        $uniqueIndexes = [];
        foreach ($indexes as $index) {
            if ($index[self::INDEXES['UNIQUE']]) {
                $uniqueIndexes[implode(',', $index['columns'])] = true;
            }
        }

        $foreignKeysWithTypes = [];
        $tableReferenceCount = [];

        // Track references to the same foreign table and determine basic relation types
        foreach ($foreignKeys as $fk) {
            $columns = $fk['columns'];
            $foreignTable = $fk['foreign_table'];
            $indexKey = implode(',', $columns);
            $hasUniqueIndex = isset($uniqueIndexes[$indexKey]);

            if (!isset($tableReferenceCount[$foreignTable])) {
                $tableReferenceCount[$foreignTable] = 0;
            }
            $tableReferenceCount[$foreignTable] += 1;

            $relationType = RELATION_TYPES['ONE-TO-MANY']; // Default to 1-N

            if ($hasUniqueIndex) {
                $relationType = RELATION_TYPES['ONE-TO-ONE'];
            }

            $foreignKeysWithTypes[] = array_merge($fk, ['relation_type' => $relationType]);
        }


        // Identify N-N relationships
        $nNRelationships = [];
        foreach ($indexes as $index) {
            if ($index[self::INDEXES['UNIQUE']] && count($index['columns']) >= 2) {
                $relatedForeignKeys = array_filter($foreignKeys, function ($fk) use ($index) {
                    return count(array_intersect($fk['columns'], $index['columns'])) > 0;
                });

                if (count($relatedForeignKeys) == 2) {
                    $foreignTables = array_unique(array_column($relatedForeignKeys, 'foreign_table'));
                    if (count($foreignTables) == 2) {
                        foreach ($foreignKeysWithTypes as &$fkWithType) {
                            if (in_array($fkWithType['columns'][0], $index['columns'])) {
                                $fkWithType['relation_type'] = RELATION_TYPES['MANY-TO-MANY'];
                                $nNRelationships[] = $fkWithType;
                            }
                        }
                    }
                }
            }
        }

        // Handle complex relationships
        foreach ($foreignKeysWithTypes as $index => $fkWithType) { //use $index, not reference !!
            $foreignTable = $fkWithType['foreign_table'];
            if ($tableReferenceCount[$foreignTable] > 1) {
                $foreignKeysWithTypes[$index]['relation_type'] = RELATION_TYPES['COMPLEX'];
            }
        }

        // Check for complex relationships by counting relation types
        $relationTypeCounts = [
            RELATION_TYPES['ONE-TO-ONE'] => 0, 
            RELATION_TYPES['ONE-TO-MANY'] => 0, 
            RELATION_TYPES['MANY-TO-MANY'] => 0, 
            RELATION_TYPES['COMPLEX'] => 0];

        // dd($foreignKeysWithTypes, $relationTypeCounts);

        foreach ($foreignKeysWithTypes as $fkWithType) {
            $relationTypeCounts[$fkWithType['relation_type']] += 1;
        }

        // dd($foreignKeysWithTypes, $relationTypeCounts);

        if (
            $relationTypeCounts[RELATION_TYPES['ONE-TO-MANY']] > 2 ||
            $relationTypeCounts[RELATION_TYPES['MANY-TO-MANY']] > 2 ||
            ($relationTypeCounts[RELATION_TYPES['ONE-TO-MANY']] > 0 && $relationTypeCounts[RELATION_TYPES['MANY-TO-MANY']] > 0) ||
            (
                ($relationTypeCounts[RELATION_TYPES['ONE-TO-MANY']] >= 2 || $relationTypeCounts[RELATION_TYPES['MANY-TO-MANY']] >= 2)
                && $relationTypeCounts[RELATION_TYPES['ONE-TO-ONE']] > 0
            )
        ) {
            foreach ($foreignKeysWithTypes as &$fkWithType) {
                if ($fkWithType['relation_type'] !== RELATION_TYPES['COMPLEX']) {
                    $fkWithType['relation_type'] = RELATION_TYPES['COMPLEX'];
                }
            }
        }

        return $foreignKeysWithTypes;
    }

    /**
     * Get the indexes for a given table.
     * 
     * @param string $tableName
     * 
     * @return array
     */
    public function getIndexes(string $tableName): array
    {
        return $this->builder->getIndexes($tableName);
    }

    /**
     * Get the index listing for a given table.
     * 
     * @param string $tableName
     * 
     * @return array
     */
    public function getIndexListing(string $tableName): array
    {
        return $this->builder->getIndexListing($tableName);
    }

    /**
     * Get the tables with indexes for the database.
     * 
     * @param string $tableName
     * 
     * @return array
     */
    public function getTablesAndForeignKeys(): array
    {
        $tableNames = $this->builder->getTableListing();

        $foreignKeys = [];
        foreach ($tableNames as $table) {
            $foreignKeys[$table] = $this->builder->getForeignKeys($table);
        }

        return $foreignKeys;
    }

    // Get the views that belong to the database.
    // getViews()

}
