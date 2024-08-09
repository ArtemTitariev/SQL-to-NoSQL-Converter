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
        Schema::table('converts', function (Blueprint $table) {
            $table->dropColumn('status_message');
            $table->dropColumn('status');
            $table->enum(
                'status',
                ['Configuring', 'Pending', 'In progress', 'Completed', 'Error']
            )->default('Configuring');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('converts', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->string('status')->nullable();
            $table->string('status_message')->nullable();
        });
    }
};
