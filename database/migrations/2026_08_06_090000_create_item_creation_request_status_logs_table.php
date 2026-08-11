<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Full audit trail of every status transition an item creation request
     * goes through — who changed it, from what to what, and any relevant
     * context (rejection reason, info request note, assigned item number,
     * sync error, etc). Distinct from the single classified_by/
     * creation_triggered_by columns on the parent table, which only ever
     * capture the *latest* actor for their respective stage.
     */
    public function up(): void
    {
        Schema::create('item_creation_request_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_creation_request_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['item_creation_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_creation_request_status_logs');
    }
};
