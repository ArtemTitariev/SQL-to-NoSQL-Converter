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
        Schema::create('circular_refs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sql_database_id')
                ->constrained('sql_databases')
                ->cascadeOnDelete();
            $table->json('circular_refs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circular_refs');
    }
};
