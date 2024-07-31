<?php

namespace App\Models;

use App\Models\SQLSchema\SqlDatabase;
use App\Models\MongoSchema\MongoDatabase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Convert extends Model
{
    use HasFactory;

    public const STATUSES = [
        'IN_PROGRESS' => 'In progress',
        'COMPLETED' => 'Completed',
        'FAILED' => 'Failed',
    ];

    protected $fillable = [
        'user_id', 'sql_database_id', 'mongo_database_id', 
        'description', 'status', 'status_message',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sqlDatabase()
    {
        return $this->belongsTo(SqlDatabase::class);
    }

    public function mongoDatabase()
    {
        return $this->belongsTo(MongoDatabase::class);
    }
}
