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
        Schema::create('id_mappings', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('table_id');
            $table->json('source_data');
            $table->char('source_data_hash', 64);

            $table->unsignedBigInteger('collection_id');
            $table->string('mapped_id');

            $table->foreign('table_id')->references('id')->on('tables')->onDelete('cascade');
            $table->foreign('collection_id')->references('id')->on('collections')->onDelete('cascade');

            $table->unique(['table_id', 'collection_id', 'source_data_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('id_mappings');
    }
};
