<?php

namespace App\Observers;

use App\Models\ItemCreationRequest;
use App\Models\User;
use App\Notifications\ItemWorkflowNotifier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class ItemCreationRequestObserver
{
    public function created(ItemCreationRequest $request): void
    {
        $request->statusLogs()->create([
            'from_status' => null,
            'to_status' => $request->status,
            'user_id' => $request->requested_by,
            'note' => 'Request submitted.',
        ]);

        $accountingUsers = $this->usersWithRole('accounting');
        $commercialUsers = $this->usersWithRole('commercial');

        $recipients = $accountingUsers->merge($commercialUsers);
        $requesterName = optional($request->requestedBy)->name ?? 'Unknown';

        foreach ($recipients as $user) {
            ItemWorkflowNotifier::send(
                recipient: $user,
                title: "New Item Request: {$request->item_name}",
                body: "A new item creation request for \"{$request->item_name}\" has been submitted by {$requesterName} and is pending accounting classification.",
                actionUrl: route('filament.item-approval.resources.item-creation-requests.edit', $request),
                actionLabel: 'Review & Classify',
                icon: 'heroicon-o-cube',
                color: 'warning'
            );
        }
    }

    public function updated(ItemCreationRequest $request): void
    {
        if (! $request->wasChanged('status')) {
            return;
        }

        $request->statusLogs()->create([
            'from_status' => $request->getOriginal('status'),
            'to_status' => $request->status,
            'user_id' => Auth::id(),
            'note' => $this->noteForTransition($request),
            'requester_response_note' => $request->getOriginal('status') === 'needs_info'
                ? $request->requester_response_note
                : null,
        ]);

        $requesterName = optional($request->requestedBy)->name ?? 'Unknown';
        $editUrl = route('filament.item-approval.resources.item-creation-requests.edit', $request);

        // 1. Stage 1 complete — notify Commercial.
        if ($request->status === 'classified') {
            $classifierName = optional($request->classifiedBy)->name ?? 'Accounting';
            foreach ($this->usersWithRole('commercial') as $user) {
                ItemWorkflowNotifier::send(
                    recipient: $user,
                    title: "Item Request Classified: {$request->item_name}",
                    body: "\"{$request->item_name}\" was classified by {$classifierName} (Group: {$request->item_group}, Category: {$request->item_service_category}). Ready to create in D365.",
                    actionUrl: $editUrl,
                    actionLabel: 'Create in D365',
                    icon: 'heroicon-o-check-badge',
                    color: 'success'
                );
            }
            return;
        }

        // 2. Rejected request was revised and resubmitted — notify Accounting again.
        if ($request->status === 'pending' && $request->getOriginal('status') === 'rejected') {
            foreach ($this->usersWithRole('accounting') as $user) {
                ItemWorkflowNotifier::send(
                    recipient: $user,
                    title: "Item Request Resubmitted: {$request->item_name}",
                    body: "The rejected request for \"{$request->item_name}\" has been revised and resubmitted by {$requesterName}.",
                    actionUrl: $editUrl,
                    actionLabel: 'Review & Classify',
                    icon: 'heroicon-o-arrow-path',
                    color: 'warning'
                );
            }
            return;
        }

        // 3. Accounting requested more info — notify Requester.
        if ($request->status === 'needs_info') {
            if ($request->requestedBy) {
                ItemWorkflowNotifier::send(
                    recipient: $request->requestedBy,
                    title: "Action Required: Info Needed for {$request->item_name}",
                    body: "Accounting has requested additional information for your item request \"{$request->item_name}\". Note: \"{$request->info_request_note}\"",
                    actionUrl: $editUrl,
                    actionLabel: 'Provide Info / Revise',
                    icon: 'heroicon-o-question-mark-circle',
                    color: 'info'
                );
            }
            return;
        }

        // 4. Accounting rejected the request — notify Requester.
        if ($request->status === 'rejected') {
            if ($request->requestedBy) {
                ItemWorkflowNotifier::send(
                    recipient: $request->requestedBy,
                    title: "Item Request Rejected: {$request->item_name}",
                    body: "Your request to create item \"{$request->item_name}\" has been rejected by Accounting. Reason: \"{$request->rejection_reason}\"",
                    actionUrl: $editUrl,
                    actionLabel: 'Revise Request',
                    icon: 'heroicon-o-x-circle',
                    color: 'danger'
                );
            }
            return;
        }
    }

    protected function usersWithRole(string $roleName): Collection
    {
        if (! Role::where('name', $roleName)->exists()) {
            Log::warning("ItemCreationRequestObserver: role '{$roleName}' does not exist — no notification sent.");
            return collect();
        }

        return User::role($roleName)->get();
    }

    // Human-readable context for the audit log entry, specific to what
    // actually changed on this transition.
    protected function noteForTransition(ItemCreationRequest $request): ?string
    {
        return match ($request->status) {
            'classified' => "Group: {$request->item_group}, Model Group: {$request->item_model_group}, ".
                "Category: {$request->item_service_category}, ".
                ($request->is_stocked ? 'Stocked' : 'Non-stocked'),
            'needs_info' => $request->info_request_note,
            'rejected' => $request->rejection_reason,
            'pending' => $request->getOriginal('status') === 'rejected'
                ? 'Revised and resubmitted by requester.'
                : ($request->getOriginal('status') === 'needs_info'
                    ? ($request->requester_response_note
                        ? 'Answered Accounting: ' . $request->requester_response_note
                        : 'Answered Accounting request.')
                    : null),
            'creating' => 'Creation in D365 queued.',
            'created' => "Item number assigned: {$request->assigned_item_number}",
            'create_failed' => $request->sync_error
                ? \Illuminate\Support\Str::limit($request->sync_error, 500)
                : null,
            default => null,
        };
    }
}
