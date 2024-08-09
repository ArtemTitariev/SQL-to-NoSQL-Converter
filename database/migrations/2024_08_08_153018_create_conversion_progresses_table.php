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
        Schema::create('conversion_progresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('convert_id')
                ->constrained('converts')
                ->cascadeOnDelete();
            $table->smallInteger('step');
            $table->enum(
                'status',
                ['Configuring', 'Pending', 'In progress', 'Completed', 'Error']
            )->default('Configuring');
            $table->text('details')->nullable();
            $table->timestamps();

            $table->unique(['convert_id', 'step']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversion_progresses');
    }
};
