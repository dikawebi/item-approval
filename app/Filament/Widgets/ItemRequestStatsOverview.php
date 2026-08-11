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

    protected function getStats(): array
    {
        $user = $this->currentUser();

        $pending = ItemCreationRequest::whereIn('status', ['pending', 'needs_info'])->count();
        $classified = ItemCreationRequest::where('status', 'classified')->count();
        $created = ItemCreationRequest::where('status', 'created')->count();
        $rejected = ItemCreationRequest::where('status', 'rejected')->count();
        $createFailed = ItemCreationRequest::where('status', 'create_failed')->count();
        $aging = ItemCreationRequest::whereIn('status', ['pending', 'needs_info', 'classified'])
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
