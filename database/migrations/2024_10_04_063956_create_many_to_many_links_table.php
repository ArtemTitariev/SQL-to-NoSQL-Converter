<?php

use App\Enums\MongoManyToManyRelation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('many_to_many_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection1_id')
            ->constrained('collections')
            ->cascadeOnDelete();
            $table->foreignId('collection2_id')
            ->constrained('collections')
            ->cascadeOnDelete();
            $table->foreignId('pivot_collection_id')
            ->constrained('collections')
            ->cascadeOnDelete();

            $table->enum('relation_type', MongoManyToManyRelation::getValues());
            $table->boolean('is_bidirectional');

            $table->json('local1_fields');
            $table->json('local2_fields');
            $table->json('foreign1_fields');
            $table->json('foreign2_fields');

            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('many_to_many_links');
    }
};
