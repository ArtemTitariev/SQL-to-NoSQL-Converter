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
        Schema::create('sql_databases', function (Blueprint $table) {
            $table->id();
            $table->string('connection_name')->unique();
            $table->string('driver', 10);
            $table->string('host');
            $table->string('port', 10);
            $table->string('database');
            $table->string('username');
            $table->string('password');
            $table->string('charset', 30);
            $table->string('collation')->nullable();
            $table->string('prefix')->nullable();
            $table->boolean('strict')->nullable();
            $table->string('engine')->nullable();
            $table->string('search_path')->nullable();
            $table->boolean('sslmode')->nullable();
            $table->boolean('encrypt')->nullable();
            $table->boolean('trust_server_certificate')->nullable();
            $table->json('options')->nullable();

            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sql_databases');
    }
};
