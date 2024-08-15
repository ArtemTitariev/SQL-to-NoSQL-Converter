<?php

namespace App\Schema\SQL;

use App\Models\SQLSchema\CircularRef;
use App\Models\SQLSchema\Column;
use App\Models\SQLSchema\ForeignKey;
use App\Models\SQLSchema\SQLDatabase;
use App\Models\SQLSchema\Table;
use App\Schema\DataTypes\RdbDataTypeRulesFactory;

use Illuminate\Support\Facades\DB;

/**
 * SQL Schema mapper
 * Perform storing relational database schema in models.
 */
class Mapper
{

    /**
     * @var App\Schema\SQL\Reader $reader
     */
    protected $reader;

    /**
     * @var \App\Models\SQLSchema\SQLDatabase $sqlDatabase
     */
    protected $sqlDatabase;

    /**
     * @var App\Schema\DataTypes\RdbDataTypeRulesInterface $dataTypeRules
     */
    protected $dataTypeRules;

    public function __construct(SQLDatabase $sqlDatabaseModel, Reader $reader)
    {
        $this->reader = $reader;
        $this->sqlDatabase = $sqlDatabaseModel;
        $this->dataTypeRules = RdbDataTypeRulesFactory::create($this->sqlDatabase->driver);
    }

    public function mapSchema()
    {
        $tables = $this->reader->getTables();

        $tableListing = $this->reader->getTableListing();

        foreach (array_chunk($tables, 10) as $tableChunk) {
            $database = $this->sqlDatabase;

            DB::transaction(function () use ($database, $tableChunk, $tableListing) {
                foreach ($tableChunk as $table) {
                    $this->mapTable($database, $table, $tableListing);
                }
            });
        }

        DB::transaction(function () {
            $this->mapCircularRefs();
        });
    }

    protected function mapTable(SQLDatabase $sqlDatabase, array $tableData, array &$allTables)
    {
        $table = new Table([
            'sql_database_id' => $sqlDatabase->id,
            'name' => $tableData['name'],
            'primary_key' => $this->reader->getPrimaryKey($tableData['name']),
        ]);
        $table->save();

        $columns = $this->reader->getColumns($tableData['name']);
        foreach ($columns as $column) {
            $this->mapColumn($table, $column);
        }

        $foreignKeys = $this->reader->getForeignKeysWithRelationType($tableData['name'], $allTables);
        foreach ($foreignKeys as $foreignKey) {
            $this->mapForeignKey($table, $foreignKey);
        }
    }

    protected function mapColumn(Table $table, array $columnData)
    {
        Column::create([
            'table_id' => $table->id,
            'name' => $columnData['name'],
            'type_name' => $columnData['type_name'],
            'type' => $columnData['type'],
            'nullable' => $columnData['nullable'],
            'convertable_types' =>  $this->dataTypeRules
                ->getSupportedTypes(
                    $columnData['type_name'],
                    $columnData['type'],
                ),
        ]);
    }

    protected function mapForeignKey(Table $table, array $foreignKeyData)
    {
        ForeignKey::create([
            'table_id' => $table->id,
            'name' => $foreignKeyData['name'],
            'columns' => $foreignKeyData['columns'],
            'foreign_schema' => $foreignKeyData['foreign_schema'],
            'foreign_table' => $foreignKeyData['foreign_table'],
            'foreign_columns' => $foreignKeyData['foreign_columns'],
            'relation_type' => $foreignKeyData['relation_type'],
        ]);
    }

    protected function mapCircularRefs()
    {
        $tablesForeignKeys = $this->reader->getTablesAndForeignKeys();

        $circularRefs = CircularRefsDetector::detect($tablesForeignKeys);

        foreach ($circularRefs as $ref) {
            CircularRef::create([
                'sql_database_id' => $this->sqlDatabase->id,
                'circular_refs' => $ref,
            ]);
        }
    }
}
