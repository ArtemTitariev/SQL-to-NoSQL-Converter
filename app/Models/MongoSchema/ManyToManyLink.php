<?php

namespace App\Models\MongoSchema;

use App\Enums\MongoManyToManyRelation;
use App\Models\SQLSchema\ForeignKey;
use App\Models\SQLSchema\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManyToManyLink extends Model
{
    use HasFactory;

    protected $table = 'many_to_many_links';

    public $timestamps = false;

    protected $fillable = [
        'collection1_id',
        'collection2_id',
        'pivot_collection_id',
        'relation_type',
        'is_bidirectional',
        'local1_fields',
        'local2_fields',
        'foreign1_fields',
        'foreign2_fields',
    ];

    protected $casts = [
        'is_bidirectional' => 'boolean',
        'relation_type' => MongoManyToManyRelation::class,
        'local1_fields' => 'array',
        'local2_fields' => 'array',
        'foreign1_fields' => 'array',
        'foreign2_fields' => 'array',
    ];

    public function collection1(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'collection1_id', 'id');
    }

    public function collection2(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'collection2_id', 'id');
    }

    public function pivotCollection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'pivot_collection_id', 'id');
    }

    public static function createFrom(
        Table $pivot,
        ForeignKey $first,
        ForeignKey $second,
        $collections,
        MongoManyToManyRelation $relationType,
        bool $isBidirectional = true
    ): ManyToManyLink {
        $requiredCollections = $collections->whereIn(
            'name',
            [$pivot->name, $first->foreign_table, $second->foreign_table]
        )->keyBy('name');

        return ManyToManyLink::create([
            'pivot_collection_id' => $requiredCollections[$pivot->name]->id,
            'collection1_id' => $requiredCollections[$first->foreign_table]->id,
            'collection2_id' => $requiredCollections[$second->foreign_table]->id,

            'relation_type' => $relationType,
            'is_bidirectional' => $isBidirectional,

            'local1_fields' => $first->columns,
            'local2_fields' => $second->columns,
            'foreign1_fields' => $first->foreign_columns,
            'foreign2_fields' =>  $second->foreign_columns,
        ]);
    }
}
