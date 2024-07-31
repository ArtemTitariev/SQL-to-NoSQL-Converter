<?php

namespace App\Models\SQLSchema;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CircularRef extends Model
{
    use HasFactory;

    public $timestamps = false;
    
    public const TYPES = [
        'DIRECT' => 'direct', 
        'INDIRECT' => 'indirect', 
        'MULTIPLE' => 'multiple',
    ];


    protected $fillable = [
        'sql_database_id', 'type', 'circular_refs',
    ];

    protected $casts = [
        'circular_refs' => 'array',
    ];

    public function database()
    {
        return $this->belongsTo(SQLDatabase::class);
    }

    public function isValidType($type)
    {
        return in_array($type, self::TYPES);
    }
}
