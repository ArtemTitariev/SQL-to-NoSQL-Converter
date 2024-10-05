<?php

use App\Enums\RelationType;
use App\Enums\MongoRelationType;
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
        Schema::create('links_embedds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fk_collection_id')
            ->constrained('collections')
            ->cascadeOnDelete();
            $table->foreignId('pk_collection_id')
            ->constrained('collections')
            ->cascadeOnDelete();
            
            $table->enum('sql_relation', RelationType::getValues());
            $table->enum('relation_type', MongoRelationType::getValues());

            $table->json('local_fields');
            // $table->json('new_field');
            // $table->json('removable_locals')->nullable();

            $table->json('foreign_fields');

            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('links_embedds');
    }
};
