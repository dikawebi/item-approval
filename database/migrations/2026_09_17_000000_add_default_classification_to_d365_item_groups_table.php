<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('d365_item_groups', function (Blueprint $table) {
            $table->string('default_item_model_group')->nullable()->after('description');
            $table->string('default_item_service_category')->nullable()->after('default_item_model_group');
        });
    }

    public function down(): void
    {
        Schema::table('d365_item_groups', function (Blueprint $table) {
            $table->dropColumn(['default_item_model_group', 'default_item_service_category']);
        });
    }
};
