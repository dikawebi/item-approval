<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_creation_requests', function (Blueprint $table) {
            $table->string('item_model_group')->nullable()->after('item_group');
        });
    }

    public function down(): void
    {
        Schema::table('item_creation_requests', function (Blueprint $table) {
            $table->dropColumn('item_model_group');
        });
    }
};
