<?php

namespace App\Models\MongoSchema;

use App\Models\Convert;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MongoDatabase extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['connection_name', 'dsn', 'database', 'options'];

    protected $casts = [
        'options' => 'array'
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        // 
    ];

    public function collections()
    {
        return $this->hasMany(Collection::class);
    }

    public function convert() {
        return $this->belongsTo(Convert::class);
    }
}
