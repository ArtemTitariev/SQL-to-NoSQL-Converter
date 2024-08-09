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
        'id', 'convert_id', 'step', 'status', 'details',
        'created_at', 'updated_at',
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
}
