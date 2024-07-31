<?php

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
        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->json('local_fields');
            $table->json('save_to');
            $table->json('old_locals');

            $table->string('embedded_collection');
            $table->json('foreign_fields');
            $table->json('old_foreigns');
            
            $table->enum('relation_type', ['1-1', '1-N', 'N-1']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('links');
    }
};
