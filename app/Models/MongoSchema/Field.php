<?php

namespace App\Models\MongoSchema;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Field extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'collection_id', 'name', 'type'
    ];

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }
    
}
