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
        Schema::create('converts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('sql_database_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('mongo_database_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->enum('status', ['In progress', 'Completed', 'Failed']);
            $table->text('status_message');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('converts');
    }
};
