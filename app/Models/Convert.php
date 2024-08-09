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

    public function getLastProgress(): ?ConversionProgress
    {
        return $this->progresses()->orderBy('step', 'desc')->first();
    }

    // public function failStep(int $step, string $details)
    // {
    //     ConversionProgress::updateOrCreate(['convert_id' => $this->id, 'step' => $step],
    //     [
    //         'is_completed' => false,
    //         'details' => $details,
    //     ]);
    // }

    // public function completeStep(int $step, string $details)
    // {
    //     ConversionProgress::updateOrCreate(['convert_id' => $this->id, 'step' => $step],
    //     [
    //         'is_completed' => true,
    //         'details' => $details,
    //     ]);
    // }
}
