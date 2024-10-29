<?php

namespace App\Models\SQLSchema;

use App\Models\IdMapping;
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
        'sql_database_id',
        'name',
        'primary_key',
        'rows_number',
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

    public function database() //: BelongsTo
    {
        return $this->belongsTo(SQLDatabase::class, 'sql_database_id', 'id');
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'id', 'sql_table_id');
    }

    public function idMappings(): HasMany
    {
        return $this->hasMany(IdMapping::class, 'table_id');
    }

    public function getOrderingColumnName(): string
    {
        return is_null($this->primary_key) ?
            $this->columns()->first()->name :
            $this->primary_key[0];
    }

    public function hasPk(): bool
    {
        return empty($this->primary_key);
    }

    public function hasCompositePk(): bool
    {
        return $this->hasPrimaryKey() && count($this->primary_key) > 1;
    }
}
