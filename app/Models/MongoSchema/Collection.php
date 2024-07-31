<?php

namespace App\Models\MongoSchema;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function fields()
    {
        return $this->hasMany(Field::class);
    }

    public function embeddings()
    {
        return $this->hasMany(Embedding::class);
    }

    public function links()
    {
        return $this->hasMany(Link::class);
    }

    public function database()
    {
        return $this->belongsTo(MongoDatabase::class);
    }
}
