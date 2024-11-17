<?php

namespace App\Schema\SQL;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Builder;
use App\Enums\RelationType;

/**
 * SQL Schema reader
 * Provide methods to "read" relational database schema.
 */
class Reader
{

    /**
     * @var Illuminate\Database\Connection
     */
    protected $connection;

    /**
     * @var Illuminate\Database\Schema\Builder
     */
    protected Builder $builder;

    /**
     * Index types
     */
    private const INDEXES = [
        'UNIQUE' => 'unique',
        'PRIMARY' => 'primary',
    ];

    /**
     * @var array
     */
    private $tableNames;

    /**
     * @var Illuminate\Database\Connection $connection;
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->builder = $connection->getSchemaBuilder();

        $this->tableNames = array_flip($this->getTableListing());
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

    // /**
    //  * Get the names of the tables that belong to the database.
    //  * 
    //  * @return array
    //  */
    // public function getTableNames(): array
    // {
    //     return $this->tableListing;
    // }

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

    /**
     * Filter foreign keys that refer to other databases.
     * Return an array with foreign keys that refer only to the current database.
     * 
     * @param string $tableName
     * 
     * @return array
     */
    private function getFilteredForeignKeys(string $tableName)
    {
        $foreignKeys = $this->getForeignKeys($tableName);
        // $tableNames = array_flip($this->getTableListing());
        // $tableNames = array_flip($this->tableListing);

        return array_filter($foreignKeys, fn($fK) => isset($this->tableNames[$fK['foreign_table']]));
    }

    /**
     * Counting the number of rows in a table. The maximum exact number 
     * is defined in the configuration constants.MAX_ROWS_LIMIT.
     * If the number of rows is greater than constants.MAX_ROWS_LIMIT, 
     * constants.MAX_ROWS_LIMIT_EXCEEDED value is returned.
     * 
     * @param string $tableName
     * 
     * @return int
     */
    public function getRowsNumber(string $tableName): int
    {
        $maxCount = config('constants.MAX_ROWS_LIMIT');
        $exceeded = config('constants.MAX_ROWS_LIMIT_EXCEEDED');

        $count = $this->connection->table($tableName)
            ->take($maxCount + 1) // Limit
            ->count();

        return $count > $maxCount ? $exceeded : $count;
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

    complex: мінімум два 1-N (або два N-N) та інші (один або декілька), будь які типи зв'язку.
        Іншими словами, два 1-N (або ж N-N) та ще хоча б один будь-який зв'язок.
        Важливо: якщо в таблиці є два або більше ключів, які посилаються 
        на одну й ту саму таблицю, це теж вважається складеним зв'язком
        (навіть якщо це два зв'язки 1-1, які посилаються на одну і ту ж таблицю).

    Self reference виділено як окремий тип зв'язку.

    Foreign key на іншу БД не дозволені - виникне помилки при спробі отримати 
        дані звідти. Тому вони просто ігноруються. Щоб уникнути помилок, 
        масив з foreign keys фільтрується.
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
        // $relationTypes = config('constants.RELATION_TYPES');

        $foreignKeys = $this->getFilteredForeignKeys($tableName);
        $indexes = $this->getIndexes($tableName);

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

            $relationType = RelationType::MANY_TO_ONE; // Default to N-1

            if ($hasUniqueIndex) {
                $relationType = RelationType::ONE_TO_ONE;
            }

            if ($foreignTable === $tableName) { // Self reference
                $relationType = RelationType::SELF_REF;
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
                        foreach ($foreignKeysWithTypes as $fKIndex => $fkWithType) { //use $index, not reference !!
                            if (
                                in_array($fkWithType['columns'][0], $index['columns'])
                                && $fkWithType['relation_type']->value !== RelationType::COMPLEX->value //----------------------------
                            ) {

                                $foreignKeysWithTypes[$fKIndex]['relation_type'] = RelationType::MANY_TO_MANY;
                                $nNRelationships[] = $fkWithType;
                            }
                        }
                    }
                }
            }
        }

        // Handle complex relationships - 
        // якщо в таблиці є 2 та більше посилань 
        // на одну і ту ж foreign таблицю - це теж complex
        foreach ($foreignKeysWithTypes as $index => $fkWithType) { //use $index, not reference !!
            $foreignTable = $fkWithType['foreign_table'];
            if ($tableReferenceCount[$foreignTable] > 1) {
                $foreignKeysWithTypes[$index]['relation_type'] = RelationType::COMPLEX;
            }
        }

        // Check for complex relationships by counting relation types
        // $relationTypeCounts = [
        //     RELATION_TYPES['ONE-TO-ONE'] => 0,
        //     RELATION_TYPES['ONE-TO-MANY'] => 0,
        //     RELATION_TYPES['MANY-TO-MANY'] => 0,
        //     RELATION_TYPES['SELF-REF'] => 0,
        //     RELATION_TYPES['COMPLEX'] => 0
        // ];
        // $relationTypeKeys = array_values($relationTypes);
        $relationTypeKeys = RelationType::getValues();

        $relationTypeCounts = array_fill_keys($relationTypeKeys, 0);

        foreach ($foreignKeysWithTypes as $fkWithType) {
            $relationTypeCounts[$fkWithType['relation_type']->value] += 1;
        }

        if (
            // $relationTypeCounts[RELATION_TYPES['ONE-TO-MANY']] > 2 ||
            // $relationTypeCounts[RELATION_TYPES['MANY-TO-MANY']] > 2 ||
            // ($relationTypeCounts[RELATION_TYPES['ONE-TO-MANY']] > 0 && $relationTypeCounts[RELATION_TYPES['MANY-TO-MANY']] > 0) ||
            // (
            //     ($relationTypeCounts[RELATION_TYPES['ONE-TO-MANY']] >= 2 || $relationTypeCounts[RELATION_TYPES['MANY-TO-MANY']] >= 2)
            //     && ($relationTypeCounts[RELATION_TYPES['ONE-TO-ONE']] > 0 || $relationTypeCounts[RELATION_TYPES['SELF-REF']] > 0)
            // )

            // якщо є (2 N-N або 2 1-N) та ще якийсь
            array_sum($relationTypeCounts) > 2 &&
            ($relationTypeCounts[RelationType::MANY_TO_ONE->value] >= 2 ||
                // $relationTypeCounts[RelationType::ONE_TO_MANY->value] >= 2 || // not used
                $relationTypeCounts[RelationType::MANY_TO_MANY->value] >= 2)
        ) {
            foreach ($foreignKeysWithTypes as $index => $fkWithType) { //use $index, not reference !!
                if ($fkWithType['relation_type']->value !== RelationType::COMPLEX->value) {
                    $foreignKeysWithTypes[$index]['relation_type'] = RelationType::COMPLEX;
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
