<?php

use App\Enums\RelationType;
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
        Schema::table('foreign_keys', function (Blueprint $table) {
            $table->dropColumn('relation_type');
            $table->enum('relation_type', RelationType::getValues());
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::table('foreign_keys', function (Blueprint $table) {
        //     $table->dropColumn('relation_type');
        //     $table->enum(
        //         'relation_type',
        //         [
        //             '1-1',
        //             '1-N',
        //             'N-N',
        //             'Self reference',
        //             'Complex multiple'
        //         ]
        //     );
        // });
    }
};
