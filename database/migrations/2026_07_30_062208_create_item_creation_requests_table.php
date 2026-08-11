<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_creation_requests', function (Blueprint $table) {
            $table->id();

            // Proposed product data (from requester)
            $table->string('item_name');
            $table->string('description')->nullable();
            $table->string('inventory_unit')->nullable();
            $table->string('purchase_unit')->nullable();
            $table->string('sales_unit')->nullable();

            // Item group: requester's guess vs accounting's actual decision
            $table->string('proposed_item_group')->nullable();
            $table->string('item_group')->nullable(); // set by accounting

            // Additional classification set by accounting, synced from D365
            $table->string('item_service_category')->nullable(); // set by accounting
            $table->boolean('is_stocked')->nullable(); // true = Stocked, false = Non-stocked, set by accounting

            // Two-stage workflow state
            $table->enum('status', [
                'pending',           // just submitted, waiting on accounting
                'needs_info',        // accounting sent back for clarification
                'rejected',          // accounting or commercial rejected outright
                'classified',        // accounting finished classification, waiting on commercial
                'creating',          // commercial triggered creation, job in flight
                'created',           // successfully created in D365
                'create_failed',     // D365 creation failed, needs attention
            ])->default('pending');

            $table->foreignId('requested_by')->constrained('users');

            // Stage 1 — Accounting
            $table->foreignId('classified_by')->nullable()->constrained('users');
            $table->timestamp('classified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('info_request_note')->nullable();

            // Stage 2 — Commercial
            $table->foreignId('creation_triggered_by')->nullable()->constrained('users');
            $table->timestamp('creation_triggered_at')->nullable();

            // D365 sync tracking
            $table->boolean('synced_to_d365')->default(false);
            $table->string('d365_item_id')->nullable();
            $table->text('sync_error')->nullable();
            $table->timestamp('synced_at')->nullable();

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_creation_requests');
    }
};
