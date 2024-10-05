<?php

namespace App\Models\MongoSchema;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Collection extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'mongo_database_id', 'name', 'schema_validator'
    ];

    protected $casts = [
        'schema_validator' => 'array',
    ];

    public function fields(): HasMany
    {
        return $this->hasMany(Field::class);
    }

    // public function embeddings(): HasMany
    // {
    //     return $this->hasMany(Embedding::class);
    // }

    // public function links(): HasMany
    // {
    //     return $this->hasMany(Link::class);
    // }

    public function linksEmbeddsFrom(): HasMany
    {
        return $this->hasMany(LinkEmbedd::class, 'fk_collection_id', 'id');
    }
    
    public function linksEmbeddsTo(): HasMany
    {
        return $this->hasMany(LinkEmbedd::class, 'pk_collection_id', 'id');
    }

    public function manyToManyPivot(): HasMany
    {
        return $this->hasMany(ManyToManyLink::class, 'pivot_collection_id', 'id');
    }

    public function database(): BelongsTo
    {
        return $this->belongsTo(MongoDatabase::class, 'mongo_database_id', 'id');
    }
}
