<?php

namespace App\Models\MongoSchema;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Field extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'collection_id', 'name', 'type'
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }
    
}
