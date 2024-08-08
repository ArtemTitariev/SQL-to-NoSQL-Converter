<?php

namespace App\Models\SQLSchema;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForeignKey extends Model
{
    use HasFactory;

    public $timestamps = false;
    
    // public const RELATION_TYPES = [
    //     'ONE-TO-ONE' => '1-1', 
    //     'ONE-TO-MANY' => '1-N', 
    //     'MANY-TO-MANY' => 'N-N', 
    //     'COMPLEX' => 'Complex multiple',
    // ];

    protected $fillable = [
        'table_id', 'name', 'columns', 'foreign_schema', 'foreign_table', 
        'foreign_columns', 'relation_type'
    ];

    protected $casts = [
        'columns' => 'array',
        'foreign_columns' => 'array',
    ];

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function isValidRelationType($type)
    {
        // return in_array($type, self::RELATION_TYPES);
        return in_array($type, RELATION_TYPES);
    }
}
