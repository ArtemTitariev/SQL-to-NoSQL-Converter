<?php

namespace App\Models;

use App\Models\SQLSchema\SQLDatabase;
use App\Models\MongoSchema\MongoDatabase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function sqlDatabase(): HasOne
    {
        return $this->hasOne(SQLDatabase::class, 'id', 'sql_database_id');
    }

    public function mongoDatabase(): HasOne
    {
        return $this->hasOne(MongoDatabase::class, 'id', 'mongo_database_id');
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

    /**
     * Delete data on database schemas. Database connection parameters remain.
     */
    public function clearData()
    {
        $sqlDatabase = $this->sqlDatabase;
        $sqlDatabase->circularRefs()->delete();
        $sqlDatabase->tables()->delete();

        $mongoDatabase = $this->mongoDatabase;
        $mongoDatabase->collections()->delete();
    }

    /**
     * Set status as `Error`
     */
    public function fail(): bool
    {
        $this->status = static::STATUSES['ERROR'];
        return $this->save();
    }

    /**
     * Set status as `Completed`
     */
    public function complete(): bool
    {
        $this->status = static::STATUSES['COMPLETED'];
        return $this->save();
    }

    /**
     * Set status as `In progress`
     */
    public function setStatusAsInProgress(): bool
    {
        $this->status = static::STATUSES['IN_PROGRESS'];

        $progress = $this->lastProgress();

        return $this->save() && $progress->setStatusAsInProgress();
    }

    public function isConfiguring(): bool
    {
        return $this->status == static::STATUSES['CONFIGURING'];
    }

    /**
     * For broadcasting channels. Check if user can access to conversion
     */
    public static function canAccess($user, $userId, $convertId): bool
    {
        $convert = Convert::find($convertId);

        if (!$convert) {
            return false;
        }

        return (int) $user->id === (int) $userId &&
            // (int) $convert->id === (int) $convertId &&
            (int) $convert->user->id === (int) $userId;
    }

    public function canBeDeleted(): bool
    {
        if ($this->lastProgress()?->isProcessing()) {
            return false;
        }

        return true;
    }

    /**
     * Update convert status
     * 
     * @throws InvalidArgumentException
     */
    public function updateStatus(string $status): bool
    {
        if (! in_array($status, self::STATUSES, true)) {
            throw new \InvalidArgumentException('Invalid convert status.');
        }

        $this->status = $status;
        return $this->save();
    }
}
