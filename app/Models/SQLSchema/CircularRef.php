<?php

namespace App\Models\SQLSchema;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CircularRef extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'sql_database_id', 'circular_refs',
    ];

    protected $casts = [
        'circular_refs' => 'array',
    ];

    public function database(): BelongsTo
    {
        return $this->belongsTo(SQLDatabase::class, 'sql_database_id', 'id');
    }

    /**
     * Отримати всі кругові залежності, що містять всі задані назви таблиць для конкретної бази даних.
     *
     * @param int $databaseId
     * @param array $tableNames
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getByAllTableNames(int $databaseId, array $tableNames)
    {
        return self::where('sql_database_id', $databaseId)
            ->where(function ($query) use ($tableNames) {
                foreach ($tableNames as $tableName) {
                    $query->whereJsonContains('circular_refs', $tableName);
                }
            })
            // ->havingRaw('JSON_LENGTH(circular_refs) = ?', count($tableNames))
            ->get();
    }
}
