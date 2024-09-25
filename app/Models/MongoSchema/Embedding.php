<?php

namespace App\Models\MongoSchema;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Embedding extends Model
{
    use HasFactory;

    public $timestamps = false;

    public const RELATION_TYPES = [
        'ONE-TO-ONE' => '1-1',
        'ONE-TO-MANY' => '1-N',
        'MANY-TO-ONE' => 'N-1',
    ];

    protected $fillable = [
        'collection_id', 'local_fields', 'save_to', 'old_locals', 
        'linked_collection', 'foreign_fields', 'relation_type'
    ];

    protected $casts = [
        'local_fields' => 'array',
        'save_to' => 'array',
        'old_locals' => 'array',
        'foreign_fields' => 'array',
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }
}
