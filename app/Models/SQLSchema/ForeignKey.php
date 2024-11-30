<?php

namespace App\Models\SQLSchema;

use App\Enums\RelationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForeignKey extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'table_id',
        'name',
        'columns',
        'foreign_schema',
        'foreign_table',
        'foreign_columns',
        'relation_type',
    ];

    protected $casts = [
        'columns' => 'array',
        'foreign_columns' => 'array',
        'relation_type'  => RelationType::class,
    ];

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function relatedTable($databaseId, $loadRelations = false): Table
    {
        return Table::with($loadRelations ? ['foreignKeys'] : [])
            ->where('sql_database_id', $databaseId)
            ->where('name', $this->foreign_table)
            ->first();
    }

    public static function relationToTableExists($databaseId, array $toForeignTables, array $excludeFkIds): bool
    {
        return Table::join('foreign_keys', 'foreign_keys.table_id', '=', 'tables.id')
            ->where('tables.sql_database_id', $databaseId)
            ->whereIn('foreign_keys.foreign_table', $toForeignTables)
            ->whereNotIn('foreign_keys.id', $excludeFkIds)
            ->exists();
    }
}
