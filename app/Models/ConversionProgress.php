<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversionProgress extends Model
{
    use HasFactory;

    protected $table = 'conversion_progresses';

    public const STATUSES = [
        'CONFIGURING' => 'Configuring',
        'PENDING' => 'Pending',
        'IN_PROGRESS' => 'In progress',
        'COMPLETED' => 'Completed',
        'ERROR' => 'Error',
    ];

    protected $fillable = [
        'id',
        'convert_id',
        'step',
        'name',
        'status',
        'details',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'step' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function convert(): BelongsTo
    {
        return $this->belongsTo(Convert::class);
    }

    public function canContinue(): bool
    {
        return $this->status === static::STATUSES['CONFIGURING'];
    }

    /**
     * Перевіряє, чи є статус завершеним.
     *
     * @return bool
     */
    public function isCompleted()
    {
        return $this->status === self::STATUSES['COMPLETED'];
    }

    /**
     * Перевіряє, чи є статус помилковим.
     *
     * @return bool
     */
    public function isError()
    {
        return $this->status === self::STATUSES['ERROR'];
    }

    /**
     * Перевіряє, чи є статус завершеним або помилковим.
     *
     * @return bool
     */
    public function isCompletedOrError()
    {
        return $this->isCompleted() || $this->isError();
    }
}
