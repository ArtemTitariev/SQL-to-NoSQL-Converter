<?php

namespace App\Models\SQLSchema;

use App\Models\Convert;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SQLDatabase extends Model
{
    use HasFactory;

    public $timestamps = false;
    
    protected $table = 'sql_databases';
    
    protected $fillable = [
        'connection_name', 'driver', 'host', 'port', 'database', 'username', 
        'password', 'charset', 'collation', 'prefix', 'strict', 'engine', 
        'search_path', 'sslmode', 'encrypt', 'trust_server_certificate', 'options'
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function tables()
    {
        return $this->hasMany(Table::class);
    }
    
    public function circularRefs()
    {
        return $this->hasMany(CircularRef::class);
    }

    public function convert() {
        return $this->belongsTo(Convert::class);
    }
}
