<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('d365_item_groups', function (Blueprint $table) {
            $table->id();
            $table->string('item_group_id')->unique(); // matches InventItemGroupId in D365
            $table->string('description')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('d365_item_groups');
    }
};
