<?php

namespace App\Models\MongoSchema;

use App\Models\Convert;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

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
    
    /**
     * @throws Illuminate\Contracts\Encryption\DecryptException
     */
    protected function dsn(): Attribute
    {
        return $this->cryptedAttribute();
    }

    /**
     * @throws Illuminate\Contracts\Encryption\DecryptException
     */
    private function cryptedAttribute(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => Crypt::decryptString($value),
            set: fn (string $value) => Crypt::encryptString($value),
        );
    }

    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class, 'mongo_database_id', 'id');
    }

    public function convert(): BelongsTo {
        return $this->belongsTo(Convert::class, 'id', 'mongo_database_id');
    }
}
