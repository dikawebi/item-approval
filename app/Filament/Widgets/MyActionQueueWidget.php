<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ItemCreationRequests\ItemCreationRequestResource;
use App\Models\ItemCreationRequest;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
class MyActionQueueWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function currentUser(): ?User
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user;
    }

    protected function getTableHeading(): string
    {
        $user = $this->currentUser();

        if ($user?->hasRole('commercial')) {
            return 'Ready for you to create in D365';
        }

        if ($user?->hasRole('accounting')) {
            return 'Waiting on your classification';
        }

        return 'Your requests needing action';
    }

    public function table(Table $table): Table
    {
        $user = $this->currentUser();

        if ($user?->hasRole('commercial')) {
            $query = ItemCreationRequest::query()->whereIn('status', ['classified', 'create_failed']);
        } elseif ($user?->hasRole('accounting')) {
            $query = ItemCreationRequest::query()->whereIn('status', ['pending', 'needs_info']);
        } else {
            // Plain requester: only their own requests, not the whole org's queue.
            $query = ItemCreationRequest::query()
                ->where('requested_by', $user?->id)
                ->whereIn('status', ['needs_info', 'rejected']);
        }

        return $table
            ->query($query)
            ->heading($this->getTableHeading())
            ->columns([
                Tables\Columns\TextColumn::make('item_name')->searchable(),
                Tables\Columns\TextColumn::make('requestedBy.name')->label('Requested by'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'needs_info' => 'info',
                        'classified' => 'primary',
                        'create_failed' => 'danger',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Time in stage')
                    ->since()
                    ->color(fn (ItemCreationRequest $record) => $record->isAging() ? 'danger' : 'gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->since()->sortable(),
            ])
            ->defaultSort('created_at', 'asc') // oldest first — the ones waiting longest
            ->recordActions([
                Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-right')
                    ->url(fn (ItemCreationRequest $record) =>
                        ItemCreationRequestResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated([5, 10, 25]);
    }
}
