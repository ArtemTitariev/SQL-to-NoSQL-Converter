<?php

namespace App\Models\SQLSchema;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Column extends Model
{
    use HasFactory;

    public $timestamps = false;
    
    protected $fillable = [
        'table_id', 'name', 'type_name', 'type', 'nullable', 'convertable_types'
    ];

    protected $casts = [
        'nullable' => 'boolean',
        'convertable_types' => 'array',
    ];

    public function table()
    {
        return $this->belongsTo(Table::class);
    }
}
