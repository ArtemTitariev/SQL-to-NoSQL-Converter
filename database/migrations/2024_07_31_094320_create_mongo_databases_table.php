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
        Schema::create('mongo_databases', function (Blueprint $table) {
            $table->id();
            $table->string('connection_name')->unique();
            $table->string('dsn', 255);
            $table->string('database');
            $table->json('options')->nullable();
            
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mongo_databases');
    }
};
