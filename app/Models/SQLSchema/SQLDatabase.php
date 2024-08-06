<?php

namespace App\Models\SQLSchema;

use App\Models\Convert;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

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

    /**
     * @throws Illuminate\Contracts\Encryption\DecryptException
     */
    protected function host(): Attribute
    {
        return $this->cryptedAttribute();
    }

    /**
     * @throws Illuminate\Contracts\Encryption\DecryptException
     */
    protected function port(): Attribute
    {
        return $this->cryptedAttribute();
    }

    /**
     * @throws Illuminate\Contracts\Encryption\DecryptException
     */
    protected function username(): Attribute
    {
        return $this->cryptedAttribute();
    }

    /**
     * @throws Illuminate\Contracts\Encryption\DecryptException
     */
    protected function password(): Attribute
    {
        return $this->cryptedAttribute();
    }

    /**
     * @throws Illuminate\Contracts\Encryption\DecryptException
     */
    private function cryptedAttribute(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Crypt::decryptString($value),
            set: fn ($value) => Crypt::encryptString($value),
        );
    }

    public function tables()
    {
        return $this->hasMany(Table::class);
    }

    public function circularRefs()
    {
        return $this->hasMany(CircularRef::class);
    }

    public function convert()
    {
        return $this->belongsTo(Convert::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->clearFields();
        });

        static::saving(function ($model) {
            $model->clearFields();
        });
    }

    private function clearFields()
    {
        $driver = $this->driver;

        $fieldsToClear = [
            'mysql' => [
                'search_path', 'sslmode', 'encrypt', 'trust_server_certificate'
            ],
            'mariadb' => [
                'search_path', 'sslmode', 'encrypt', 'trust_server_certificate'
            ],
            'pgsql' => [
                'collation', 'strict', 'engine', 'encrypt', 'trust_server_certificate'
            ],
            'sqlsrv' => [
                'collation', 'strict', 'engine', 'search_path', 'sslmode', 'encrypt'
            ]
        ];

        $fields = $fieldsToClear[$driver] ?? [];

        foreach ($fields as $field) {
            $this->$field = null;
        }
    }
}
