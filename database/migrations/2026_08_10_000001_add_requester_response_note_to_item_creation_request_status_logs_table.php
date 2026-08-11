<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_creation_request_status_logs', function (Blueprint $table) {
            $table->text('requester_response_note')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('item_creation_request_status_logs', function (Blueprint $table) {
            $table->dropColumn('requester_response_note');
        });
    }
};
