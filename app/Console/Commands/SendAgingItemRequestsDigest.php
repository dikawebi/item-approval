<?php

namespace App\Console\Commands;

use App\Models\ItemCreationRequest;
use App\Models\User;
use App\Notifications\ItemWorkflowNotifier;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class SendAgingItemRequestsDigest extends Command
{
    protected $signature = 'item-requests:aging-digest';

    protected $description = 'Send a once-daily digest to Accounting/Commercial listing item requests stuck 48h+ in their current stage';

    public function handle(): int
    {
        $awaitingAccounting = ItemCreationRequest::aging()
            ->whereIn('status', ['pending', 'needs_info'])
            ->get();

        $awaitingCommercial = ItemCreationRequest::aging()
            ->where('status', 'classified')
            ->get();

        $sent = 0;
        $sent += $this->notifyGroup('accounting', $awaitingAccounting, 'classification');
        $sent += $this->notifyGroup('commercial', $awaitingCommercial, 'D365 creation');

        $this->info("Aging digest: {$awaitingAccounting->count()} awaiting accounting, ".
            "{$awaitingCommercial->count()} awaiting commercial. {$sent} notification(s) sent.");

        return self::SUCCESS;
    }

    protected function notifyGroup(string $roleName, Collection $requests, string $waitingFor): int
    {
        if ($requests->isEmpty()) {
            return 0;
        }

        $recipients = $this->usersWithRole($roleName);
        $count = $requests->count();
        $list = $requests
            ->map(fn (ItemCreationRequest $r) => "• {$r->item_name} — waiting {$r->updated_at->diffForHumans(null, true)}")
            ->implode("\n");

        $indexUrl = route('filament.item-approval.resources.item-creation-requests.index');

        foreach ($recipients as $user) {
            ItemWorkflowNotifier::send(
                recipient: $user,
                title: "{$count} item request(s) waiting 48h+ for {$waitingFor}",
                body: $list,
                actionUrl: $indexUrl,
                actionLabel: 'Review Queue',
                icon: 'heroicon-o-exclamation-circle',
                color: 'danger'
            );
        }

        return $recipients->count();
    }

    protected function usersWithRole(string $roleName): Collection
    {
        if (! Role::where('name', $roleName)->exists()) {
            Log::warning("SendAgingItemRequestsDigest: role '{$roleName}' does not exist — no notification sent.");
            return Collection::make();
        }

        return User::role($roleName)->get();
    }
}
