<?php

namespace App\Models\SQLSchema;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    use HasFactory;

    public $timestamps = false;
    
    protected $fillable = [
        'sql_database_id', 'name', 'primary_key'
    ];

    protected $casts = [
        'primary_key' => 'array',
    ];

    public function columns()
    {
        return $this->hasMany(Column::class);
    }

    public function foreignKeys()
    {
        return $this->hasMany(ForeignKey::class);
    }

    public function database()
    {
        return $this->belongsTo(SQLDatabase::class);
    }
}
