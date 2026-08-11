<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class ItemCreationRequest extends Model
{
    protected $fillable = [
        'item_name',
        'description',
        'unit',
        'is_used_in_project',
        'proposed_item_group',
        'item_group',
        'item_model_group',
        'item_service_category',
        'is_stocked',
        'status',
        'requested_by',
        'classified_by',
        'classified_at',
        'rejection_reason',
        'info_request_note',
        'requester_response_note',
        'creation_triggered_by',
        'creation_triggered_at',
        'assigned_item_number',
        'synced_to_d365',
        'd365_item_id',
        'sync_error',
        'synced_at',
    ];

    protected $casts = [
        'is_stocked' => 'boolean',
        'is_used_in_project' => 'boolean',
        'classified_at' => 'datetime',
        'creation_triggered_at' => 'datetime',
        'synced_at' => 'datetime',
        'synced_to_d365' => 'boolean',
    ];

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function classifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'classified_by');
    }

    public function creationTriggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creation_triggered_by');
    }

    // Full audit trail of every status transition, newest first.
    public function statusLogs(): HasMany
    {
        return $this->hasMany(ItemCreationRequestStatusLog::class)->latest();
    }

    // Stage 1: can Accounting still act on this?
    public function canBeClassified(): bool
    {
        return in_array($this->status, ['pending', 'needs_info']);
    }

    // Stage 2: is this ready for Commercial to trigger D365 creation?
    public function canBeCreatedInD365(): bool
    {
        return in_array($this->status, ['classified', 'create_failed']);
    }

    // Was this rejected, and can the requester revise + resubmit it?
    public function canBeRevised(): bool
    {
        return $this->status === 'rejected';
    }

    // Should the basic item fields be editable right now (new, or being revised)?
    public function fieldsAreEditable(): bool
    {
        return in_array($this->status, ['pending', 'needs_info', 'rejected']);
    }

    public function isFullyClassified(): bool
    {
        return filled($this->item_group)
            && filled($this->item_model_group)
            && filled($this->item_service_category)
            && ! is_null($this->is_stocked);
    }

    // Has this request been sitting in its current stage too long?
    // Uses updated_at as a proxy for "time since last status change" (every
    // status transition goes through ->update()). Threshold is calendar
    // hours, not strict business days — a reasonable approximation, not
    // an exact business-calendar calculation.
    public function isAging(): bool
    {
        return in_array($this->status, ['pending', 'needs_info', 'classified'])
            && $this->updated_at !== null
            && $this->updated_at->lt(now()->subHours(48));
    }

    // Query scope mirroring isAging() — requests stuck 48h+ in a stage
    // that's still waiting on a human (pending/needs_info/classified).
    public function scopeAging(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'needs_info', 'classified'])
            ->where('updated_at', '<=', now()->subHours(48));
    }
}
