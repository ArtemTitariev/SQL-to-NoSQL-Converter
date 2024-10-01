<?php

namespace App\Models\SQLSchema;

use App\Enums\RelationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'relation_type'  => RelationType::class,
    ];

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    // public function isValidRelationType($type)
    // {
    //     // return in_array($type, self::RELATION_TYPES);
    //     return in_array($type, config('constants.RELATION_TYPES'));
    // }
}
