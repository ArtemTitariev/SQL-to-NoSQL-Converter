<?php

namespace App\Models\MongoSchema;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Link extends Model
{
    use HasFactory;
    
    public $timestamps = false;

    public const RELATION_TYPES = [
        'ONE-TO-ONE' => '1-1',
        'ONE-TO-MANY' => '1-N',
        'MANY-TO-MANY' => 'N-N',
        'COMPLEX' => 'Complex multiple',
    ];

    protected $fillable = [
        'collection_id', 'local_fields', 'save_to', 'old_locals', 
        'embedded_collection', 'foreign_fields', 'old_foreigns', 
        'relation_type'
    ];

    protected $casts = [
        'local_fields' => 'array',
        'save_to' => 'array',
        'old_locals' => 'array',
        'foreign_fields' => 'array',
        'old_foreigns' => 'array',
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }
}
