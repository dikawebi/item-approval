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
        Schema::table('d365_item_groups', function (Blueprint $table) {
            $table->foreignId('number_sequence_id')
                ->nullable()
                ->after('description')
                ->constrained('number_sequences')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('d365_item_groups', function (Blueprint $table) {
            $table->dropForeign(['number_sequence_id']);
            $table->dropColumn('number_sequence_id');
        });
    }
};
