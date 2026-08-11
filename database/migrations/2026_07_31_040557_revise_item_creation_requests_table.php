<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_creation_requests', function (Blueprint $table) {
            $table->string('unit')->nullable()->after('description');
            $table->boolean('is_used_in_project')->nullable()->after('unit');
        });

        // Separate closure so Postgres handles the add + drop cleanly.
        Schema::table('item_creation_requests', function (Blueprint $table) {
            $table->dropColumn(['inventory_unit', 'purchase_unit', 'sales_unit']);
        });
    }

    public function down(): void
    {
        Schema::table('item_creation_requests', function (Blueprint $table) {
            $table->string('inventory_unit')->nullable();
            $table->string('purchase_unit')->nullable();
            $table->string('sales_unit')->nullable();
            $table->dropColumn(['unit', 'is_used_in_project']);
        });
    }
};
