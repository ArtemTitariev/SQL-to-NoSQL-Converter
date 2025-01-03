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
        Schema::table('mongo_databases', function (Blueprint $table) {
            $table->string('dsn', 4500)->change();
            $table->string('database', 150)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mongo_databases', function (Blueprint $table) {
            // $table->string('dsn', 255)->change();
        });
    }
};
