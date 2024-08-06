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

        $result = array_filter($indexes, function($index) {
            return isset($index['primary']) && $index['primary'] === true;
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
     * Get the tablws with indexes for the database.
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
