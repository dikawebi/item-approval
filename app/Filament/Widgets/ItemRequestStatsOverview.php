<?php

namespace App\Filament\Widgets;

use App\Models\ItemCreationRequest;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class ItemRequestStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function currentUser(): ?User
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user;
    }

    /**
     * Base query for all stats. Requesters see only their own numbers;
     * staff see org-wide figures.
     */
    protected function scopedQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return ItemCreationRequest::forAudience($this->currentUser());
    }

    protected function getStats(): array
    {
        $pending = $this->scopedQuery()->whereIn('status', ['pending', 'needs_info'])->count();
        $classified = $this->scopedQuery()->where('status', 'classified')->count();
        $created = $this->scopedQuery()->where('status', 'created')->count();
        $rejected = $this->scopedQuery()->where('status', 'rejected')->count();
        $createFailed = $this->scopedQuery()->where('status', 'create_failed')->count();
        $aging = $this->scopedQuery()->whereIn('status', ['pending', 'needs_info', 'classified'])
            ->where('updated_at', '<=', now()->subHours(48))
            ->count();

        $stats = [
            Stat::make('Awaiting Accounting', $pending)
                ->description('Pending or needs info')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pending > 0 ? 'warning' : 'gray'),

            Stat::make('Awaiting Commercial', $classified)
                ->description('Classified, ready to create in D365')
                ->descriptionIcon('heroicon-m-cloud-arrow-up')
                ->color($classified > 0 ? 'primary' : 'gray'),

            Stat::make('Created in D365', $created)
                ->description('Successfully completed')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Rejected', $rejected)
                ->description('Awaiting requester revision')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($rejected > 0 ? 'danger' : 'gray'),

            Stat::make('Aging 48h+', $aging)
                ->description('Stuck in current stage — needs a nudge')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color($aging > 0 ? 'danger' : 'gray'),
        ];

        if ($createFailed > 0) {
            $stats[] = Stat::make('Create Failed', $createFailed)
                ->description('Needs attention — check sync_error')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger');
        }

        return $stats;
    }
}
