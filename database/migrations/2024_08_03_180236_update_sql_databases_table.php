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
        Schema::table('sql_databases', function (Blueprint $table) {
            $table->string('host', 700)->change();
            $table->string('port', 200)->change();
            $table->string('database', 150)->change();
            $table->string('username', 300)->change();
            $table->string('password', 700)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sql_databases', function (Blueprint $table) {
            // $table->string('password')->nullable(false)->change();
        });
    }
};
