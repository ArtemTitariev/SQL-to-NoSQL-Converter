<?php

namespace App\Models;

use App\Models\MongoSchema\Collection;
use App\Models\SQLSchema\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdMapping extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'table_id',
        'source_data',
        'source_data_hash',
        
        'collection_id',
        'mapped_id',
    ];

    protected $casts = [
        'source_data' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($mapping) {
            // $mapping->source_data_hash = hash('sha256', json_encode($mapping->source_data));
            $mapping->source_data_hash = static::makeHash($mapping->source_data);
        });
    }

    public static function makeHash($jsonData) {
        return hash('sha256', json_encode($jsonData));
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'table_id');
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'collection_id');
    }
}
