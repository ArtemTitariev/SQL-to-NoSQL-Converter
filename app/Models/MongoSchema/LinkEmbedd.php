<?php

namespace App\Models\MongoSchema;

use App\Enums\RelationType;
use App\Enums\MongoRelationType;
use App\Models\SQLSchema\ForeignKey;
use App\Models\SQLSchema\Table;
use App\Services\Support\EncryptsIdentifier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkEmbedd extends Model
{
    use HasFactory, EncryptsIdentifier;

    protected $table = 'links_embedds';

    public $timestamps = false;

    protected $fillable = [
        'fk_collection_id',
        'pk_collection_id',
        'sql_relation',
        'relation_type',
        'local_fields',
        // 'new_field',
        // 'removable_locals',
        'foreign_fields',
    ];

    protected $casts = [
        'sql_relation' => RelationType::class,
        'relation_type' => MongoRelationType::class,
        'local_fields' => 'array',
        // 'removable_locals' => 'array',
        'foreign_fields' => 'array',
    ];

    public function fkCollection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'fk_collection_id', 'id');
    }

    public function pkCollection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'pk_collection_id', 'id');
    }

    public static function createLink(
        Table $table,
        ForeignKey $fk,
        $collections
    ): LinkEmbedd {
        return static::createFrom(
            $table,
            $fk,
            $collections,
            MongoRelationType::LINKING
        );
    }

    public static function createEmbedding(
        Table $table,
        ForeignKey $fk,
        $collections
    ): LinkEmbedd {
        return static::createFrom(
            $table,
            $fk,
            $collections,
            MongoRelationType::EMBEDDING
        );
    }

    private static function createFrom(
        Table $table,
        ForeignKey $fk,
        $collections,
        MongoRelationType $relationType
    ): LinkEmbedd {
        $requiredCollections = $collections->whereIn('name', [$table->name, $fk->foreign_table])->keyBy('name');

        return LinkEmbedd::create([
            'fk_collection_id' => $requiredCollections[$table->name]->id,
            'pk_collection_id' => $requiredCollections[$fk->foreign_table]->id,
            'sql_relation' => $fk->relation_type,
            'relation_type' => $relationType,
            'local_fields' => $fk->columns,
            'foreign_fields' => $fk->foreign_columns,
        ]);
    }
}
