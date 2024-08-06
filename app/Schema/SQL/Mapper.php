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
class Mapper {
    
    /**
     * @var App\Schema\SQL\Reader $reader
     */
    protected $reader;

    /**
     * @var App\Schema\DataTypes\RdbDataTypeRulesInterface $dataTypeRules
     */
    protected $dataTypeRules;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    public function mapSchema(SQLDatabase $sqlDatabase)
    {
        $tables = $this->reader->getTables();
        $this->dataTypeRules = RdbDataTypeRulesFactory::create($sqlDatabase->driver);
        
        dd($this->dataTypeRules);

        foreach (array_chunk($tables, 10) as $tableChunk) {
            DB::transaction(function () use ($sqlDatabase, $tableChunk) {
                foreach ($tableChunk as $index => $table) {
                    $this->mapTable($sqlDatabase, $table);
                }
            });
        }

        // DB::transaction(function () {
        //     $this->mapCircularRefs();
        // });
        $this->dataTypeRules = null;
    }

    protected function mapTable(SQLDatabase $sqlDatabase, array $tableData)
    {
        $table = new Table([
            'sql_database_id' => $sqlDatabase->id,
            'name' => $tableData['name'],
            'primary_key' => $this->reader->getPrimaryKey($tableData['name']),
        ]);

        // $table->save();

        $columns = $this->reader->getColumns($tableData['name']);
        foreach ($columns as $column) {
            $this->mapColumn($table, $column);
        }

        $foreignKeys = $this->reader->getForeignKeys($tableData['name']);
        foreach ($foreignKeys as $foreignKey) {
            $this->mapForeignKey($table, $foreignKey);
        }
    }

    protected function mapColumn(Table $table, array $columnData)
    {
        $column = new Column([
            'table_id' => $table->id,
            'name' => $columnData['name'],
            'type_name' => $columnData['type_name'],
            'type' => $columnData['type'],
            'nullable' => $columnData['nullable'],
            'convertable_types' => $columnData['convertable_types']
        ]);
        $column->save();
    }

    protected function mapForeignKey(Table $table, array $foreignKeyData)
    {
        $foreignKey = new ForeignKey([
            'table_id' => $table->id,
            'name' => $foreignKeyData['name'],
            'columns' => $foreignKeyData['columns'],
            'foreign_schema' => $foreignKeyData['foreign_schema'],
            'foreign_table' => $foreignKeyData['foreign_table'],
            'foreign_columns' => $foreignKeyData['foreign_columns'],
            'relation_type' => $foreignKeyData['relation_type']
        ]);
        $foreignKey->save();
    }

    protected function mapCircularRefs() 
    {
        $tablesForeignKeys = $this->reader->getTablesAndForeignKeys();

        return CircularRefsDetector::detect($tablesForeignKeys);

    }

}