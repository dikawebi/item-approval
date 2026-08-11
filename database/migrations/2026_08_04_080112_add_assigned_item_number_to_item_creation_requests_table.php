<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_creation_requests', function (Blueprint $table) {
            $table->string('assigned_item_number')->nullable()->after('creation_triggered_at');
        });
    }

    public function down(): void
    {
        Schema::table('item_creation_requests', function (Blueprint $table) {
            $table->dropColumn('assigned_item_number');
        });
    }
};
