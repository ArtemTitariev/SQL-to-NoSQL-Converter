<?php

namespace App\Models\SQLSchema;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CircularRef extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'sql_database_id', 'circular_refs',
    ];

    protected $casts = [
        'circular_refs' => 'array',
    ];

    public function database()
    {
        return $this->belongsTo(SQLDatabase::class, 'sql_database_id', 'id');
    }
}
