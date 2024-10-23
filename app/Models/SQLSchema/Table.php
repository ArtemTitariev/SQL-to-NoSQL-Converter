<?php

namespace App\Models\SQLSchema;

use App\Models\MongoSchema\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Table extends Model
{
    use HasFactory;

    public $timestamps = false;
    
    protected $fillable = [
        'sql_database_id', 'name', 'primary_key', 'rows_number',
    ];

    protected $casts = [
        'primary_key' => 'array',
        'rows_number' => 'integer',
    ];

    public function columns(): HasMany
    {
        return $this->hasMany(Column::class);
    }

    public function foreignKeys(): HasMany
    {
        return $this->hasMany(ForeignKey::class);
    }

    public function database()//: BelongsTo
    {
        return $this->belongsTo(SQLDatabase::class, 'sql_database_id', 'id');
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'id', 'sql_table_id');
    }
}
