<?php

namespace App\Models\MongoSchema;

use App\Models\SQLSchema\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Collection extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'mongo_database_id',
        'name',
        'sql_table_id',
        'schema_validator'
    ];

    protected $casts = [
        'schema_validator' => 'array',
    ];

    public function fields(): HasMany
    {
        return $this->hasMany(Field::class);
    }

    // public function embeddings(): HasMany
    // {
    //     return $this->hasMany(Embedding::class);
    // }

    // public function links(): HasMany
    // {
    //     return $this->hasMany(Link::class);
    // }

    public function linksEmbeddsFrom(): HasMany
    {
        return $this->hasMany(LinkEmbedd::class, 'fk_collection_id', 'id');
    }

    public function linksEmbeddsTo(): HasMany
    {
        return $this->hasMany(LinkEmbedd::class, 'pk_collection_id', 'id');
    }

    public function manyToManyPivot(): HasMany
    {
        return $this->hasMany(ManyToManyLink::class, 'pivot_collection_id', 'id');
    }

    public function database(): BelongsTo
    {
        return $this->belongsTo(MongoDatabase::class, 'mongo_database_id', 'id');
    }

    public function sqlTable(): HasOne
    {
        return $this->hasOne(Table::class, 'id', 'sql_table_id');
    }

    public function getFilteredDataForGraph(): object
    {
        $data = [
            'collectionName' => $this->name,
            'linksEmbeddsFrom' => [],
            'manyToManyPivot' => [],
        ];

        foreach ($this->linksEmbeddsFrom as $le) {
            $data['linksEmbeddsFrom'][] = (object) [
                'fkCollectionName' => $this->name,
                'pkCollectionName' => $le->pkCollection->name,
                'relationType' => __($le->relation_type->value),
            ];
        }

        foreach ($this->manyToManyPivot as $nn) {
            $data['manyToManyPivot'][] = (object) [
                'pivotCollectionName' => $this->name,
                'collection1Name' => $nn->collection1->name,
                'collection2Name' => $nn->collection2->name,
                'relationType' => __($nn->relation_type->value),
            ];
        }

        return (object) $data;
    }
}
