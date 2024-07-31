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
        Schema::create('foreign_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('name');
            $table->json('columns');
            $table->string('foreign_schema');
            $table->string('foreign_table');
            $table->json('foreign_columns');
            $table->enum('relation_type', ['1-1', '1-N', 'N-N', 'Complex multiple']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('foreign_keys');
    }
};
