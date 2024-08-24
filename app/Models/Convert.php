<?php

namespace App\Models;

use App\Models\SQLSchema\SQLDatabase;
use App\Models\MongoSchema\MongoDatabase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Convert extends Model
{
    use HasFactory;

    public const STATUSES = [
        'CONFIGURING' => 'Configuring',
        'PENDING' => 'Pending',
        'IN_PROGRESS' => 'In progress',
        'COMPLETED' => 'Completed',
        'ERROR' => 'Error',
    ];

    protected $fillable = [
        'user_id',
        'sql_database_id',
        'mongo_database_id',
        'description',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sqlDatabase(): BelongsTo
    {
        return $this->belongsTo(SQLDatabase::class, 'sql_database_id', 'id');
    }

    public function mongoDatabase(): BelongsTo
    {
        return $this->belongsTo(MongoDatabase::class, 'mongo_database_id', 'id');
    }

    public function progresses(): HasMany
    {
        return $this->hasMany(ConversionProgress::class);
    }

    public function lastProgress(): ?ConversionProgress
    {
        return $this->progresses()->orderBy('step', 'desc')->first();
    }

    public function lastCompletedStep(): ?int
    {
        return $this->progresses()
            ->where('status', ConversionProgress::STATUSES['COMPLETED'])
            ->max('step');
    }

    public function clearData()
    {
        $sqlDatabase = $this->sqlDatabase;
        $sqlDatabase->circularRefs()->delete();
        $sqlDatabase->tables()->delete();

        $mongoDatabase = $this->mongoDatabase;
        $mongoDatabase->collections()->delete();
    }

    public function fail()
    {
        $this->status = static::STATUSES['ERROR'];
        $this->save();
    }

    public function isConfiguring(): bool 
    {
        return $this->status == static::STATUSES['CONFIGURING'];
    }
}
