<?php

namespace App\Filament\Resources\ItemCreationRequests;

use App\Filament\Resources\ItemCreationRequests\Pages;
use App\Filament\Resources\ItemCreationRequests\RelationManagers;
use App\Jobs\CreateReleasedProductInD365;
use App\Models\D365ItemGroup;
use App\Models\D365ItemModelGroup;
use App\Models\ItemCreationRequest;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
class ItemCreationRequestResource extends Resource
{
    protected static ?string $model = ItemCreationRequest::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Item Requests';

    protected static function currentUser(): ?User
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user;
    }

    public static function getNavigationBadge(): ?string
    {
        $user = static::currentUser();

        if ($user?->hasRole('commercial')) {
            return (string) static::getModel()::whereIn('status', ['classified', 'create_failed'])->count();
        }

        return (string) static::getModel()::whereIn('status', ['pending', 'needs_info'])->count();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Placeholder::make('rejection_notice')
                ->label('Rejected — please revise and resubmit')
                ->content(fn (?ItemCreationRequest $record) => $record?->rejection_reason)
                ->visible(fn (?ItemCreationRequest $record) => $record?->status === 'rejected'),

            Forms\Components\Placeholder::make('info_request_notice')
                ->label('Accounting requested more information')
                ->content('Read the note below, update the requested fields, then click "Respond to Accounting" or Save. This will send the request back to Accounting.')
                ->visible(fn (?ItemCreationRequest $record) => $record?->status === 'needs_info')
                ->hintColor('warning'),

            Forms\Components\Placeholder::make('info_request_note_display')
                ->label('Accounting clarification')
                ->content(fn (?ItemCreationRequest $record) => $record?->info_request_note)
                ->visible(fn (?ItemCreationRequest $record) => $record?->status === 'needs_info'
                    && filled($record?->info_request_note)),

            Forms\Components\Textarea::make('requester_response_note')
                ->label('Your response to Accounting')
                ->placeholder('Explain what you changed or answer the clarification request here')
                ->visible(fn (?ItemCreationRequest $record) => $record?->status === 'needs_info')
                ->disabled(fn (?ItemCreationRequest $record) => $record && !$record->fieldsAreEditable())
                ->columnSpanFull(),

            Forms\Components\TextInput::make('item_name')
                ->required()
                ->disabled(fn (?ItemCreationRequest $record) => $record && !$record->fieldsAreEditable())
                ->maxLength(255),

            Forms\Components\Textarea::make('description')
                ->disabled(fn (?ItemCreationRequest $record) => $record && !$record->fieldsAreEditable()),

            Forms\Components\TextInput::make('unit')
                ->label('Unit of Measure')
                ->required()
                ->disabled(fn (?ItemCreationRequest $record) => $record && !$record->fieldsAreEditable())
                ->helperText('Used for inventory, purchase, and sales unit in D365.'),

            Forms\Components\Radio::make('is_used_in_project')
                ->label('Used in a project?')
                ->boolean()
                ->inline()
                ->disabled(fn (?ItemCreationRequest $record) => $record && !$record->fieldsAreEditable()),

            Forms\Components\TextInput::make('proposed_item_group')
                ->label('Proposed item group (requester)')
                ->disabled()
                ->helperText('Advisory only — accounting makes the final call via the Classify action.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item_name')->searchable(),
                Tables\Columns\TextColumn::make('requestedBy.name')->label('Requested by'),
                Tables\Columns\IconColumn::make('is_used_in_project')->label('For project')->boolean(),
                Tables\Columns\TextColumn::make('item_group')->label('Item group')->placeholder('—'),
                Tables\Columns\TextColumn::make('item_model_group')->label('Model group')->placeholder('—'),
                Tables\Columns\TextColumn::make('item_service_category')->label('Item/service category')->placeholder('—'),
                Tables\Columns\IconColumn::make('is_stocked')->label('Stocked')->boolean(),
                Tables\Columns\TextColumn::make('Item Creation Status')->getStateUsing(function (ItemCreationRequest $record) {
                    return match ($record->status) {
                        'pending'       => 'Pending (Accounting)',
                        'needs_info'    => 'Needs Info (Accounting)',
                        'classified'    => 'Classified (Commercial)',
                        'creating'      => '⟳ Creating in D365…',
                        'created'       => 'Created in D365',
                        'rejected'      => 'Rejected (Requester)',
                        'create_failed' => 'Create Failed (Commercial)',
                        default         => 'Unknown',
                    };
                })
                    ->badge()
                    ->tooltip(fn (ItemCreationRequest $record) => $record->status === 'create_failed'
                        ? \Illuminate\Support\Str::limit($record->sync_error, 300)
                        : null)
                    ->color(fn (string $state): string => match ($state) {
                        'Pending (Accounting)'       => 'warning',
                        'Needs Info (Accounting)'    => 'info',
                        'Classified (Commercial)'    => 'primary',
                        '⟳ Creating in D365…'       => 'warning',
                        'Created in D365'            => 'success',
                        'Rejected (Requester)',
                        'Create Failed (Commercial)' => 'danger',
                        default                      => 'gray',
                    }),

                Tables\Columns\TextColumn::make('assigned_item_number')
                    ->label('Item No.')
                    ->placeholder('—')
                    ->copyable()
                    ->copyMessage('Item number copied')
                    ->searchable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Time in stage')
                    ->since()
                    ->color(fn (ItemCreationRequest $record) => $record->isAging() ? 'danger' : 'gray')
                    ->tooltip(fn (ItemCreationRequest $record) => $record->isAging()
                        ? 'Sitting in this stage 48h+ — needs attention'
                        : null)
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'Pending (Accounting)',
                    'needs_info' => 'Needs Info',
                    'classified' => 'Classified (Commercial)',
                    'creating' => 'Creating',
                    'created' => 'Created',
                    'rejected' => 'Rejected',
                    'create_failed' => 'Create Failed',
                ]),
            ])
            ->recordActions([
                static::getClassifyAction(),
                static::getNeedsInfoAction(),
                static::getRejectAction(),
                static::getCreateInD365Action(),
                static::getReviseAction(),
                ViewAction::make(),
                static::getViewErrorAction(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                static::getBulkCreateInD365Action(),
            ]);
    }

    protected static function resolveItemGroup(?string $itemGroupId): ?D365ItemGroup
    {
        if (blank($itemGroupId)) {
            return null;
        }

        return D365ItemGroup::query()->where('item_group_id', $itemGroupId)->first();
    }

    public static function getClassifyAction(): Action
    {
        return Action::make('classify')
            ->label('Classify & Assign')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (?ItemCreationRequest $record) => $record?->canBeClassified()
                && static::currentUser()?->hasRole('accounting'))
            ->schema([
                Forms\Components\Select::make('item_group')
                    ->label('Item Group')
                    ->options(fn () => D365ItemGroup::selectOptions())
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (callable $set, ?string $state) {
                        $group = static::resolveItemGroup($state);

                        if ($group?->hasDefaultClassification()) {
                            $set('item_model_group', $group->default_item_model_group);
                            $set('item_service_category', $group->default_item_service_category);
                            $set('is_stocked', $group->default_item_model_group === 'Service' ? 0 : 1);
                        } else {
                            // No default on file for this group (e.g. newly synced
                            // from D365) — clear so Accounting classifies manually.
                            $set('item_model_group', null);
                            $set('item_service_category', null);
                            $set('is_stocked', null);
                        }
                    })
                    ->helperText('Required — becomes ProductGroupId in D365.'),

                Forms\Components\Select::make('item_model_group')
                    ->label('Item Model Group')
                    ->options(fn () => D365ItemModelGroup::selectOptions())
                    ->searchable()
                    ->required()
                    ->disabled(fn ($get) => static::resolveItemGroup($get('item_group'))?->hasDefaultClassification() ?? false)
                    ->dehydrated()
                    ->helperText('Auto-filled and locked from the Item Group\'s default classification.'),

                Forms\Components\Select::make('item_service_category')
                    ->label('Item/Service Category')
                    ->options([
                        'item' => 'Item',
                        'service' => 'Service',
                    ])
                    ->required()
                    ->disabled(fn ($get) => static::resolveItemGroup($get('item_group'))?->hasDefaultClassification() ?? false)
                    ->dehydrated(),

                Forms\Components\Radio::make('is_stocked')
                    ->label('Stock or Non-stock')
                    ->options([
                        1 => 'Stocked',
                        0 => 'Non-stocked',
                    ])
                    ->required()
                    ->disabled(fn ($get) => static::resolveItemGroup($get('item_group'))?->hasDefaultClassification() ?? false)
                    ->dehydrated(),
            ])
            ->action(function (ItemCreationRequest $record, array $data) {
                $record->update([
                    'item_group' => $data['item_group'],
                    'item_model_group' => $data['item_model_group'],
                    'item_service_category' => $data['item_service_category'],
                    'is_stocked' => (bool) $data['is_stocked'],
                    'status' => 'classified',
                    'classified_by' => static::currentUser()?->id,
                    'classified_at' => now(),
                ]);
            })
            ->requiresConfirmation();
    }

    public static function getNeedsInfoAction(): Action
    {
        return Action::make('needsInfo')
            ->label('Request Info')
            ->icon('heroicon-o-question-mark-circle')
            ->color('info')
            ->visible(fn (?ItemCreationRequest $record) => $record?->canBeClassified()
                && static::currentUser()?->hasRole('accounting'))
            ->schema([
                Forms\Components\Textarea::make('info_request_note')
                    ->label('What needs clarifying?')
                    ->required(),
            ])
            ->action(fn (ItemCreationRequest $record, array $data) => $record->update([
                'status' => 'needs_info',
                'info_request_note' => $data['info_request_note'],
            ]));
    }

    public static function getRejectAction(): Action
    {
        return Action::make('reject')
            ->label('Reject')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (?ItemCreationRequest $record) => $record?->canBeClassified()
                && static::currentUser()?->hasRole('accounting'))
            ->schema([
                Forms\Components\Textarea::make('rejection_reason')->required(),
            ])
            ->action(fn (ItemCreationRequest $record, array $data) => $record->update([
                'status' => 'rejected',
                'rejection_reason' => $data['rejection_reason'],
                'classified_by' => static::currentUser()?->id,
            ]))
            ->requiresConfirmation();
    }

    public static function getCreateInD365Action(): Action
    {
        return Action::make('createInD365')
            ->label('Create in D365')
            ->icon('heroicon-o-cloud-arrow-up')
            ->color('success')
            ->visible(fn (?ItemCreationRequest $record) => $record?->canBeCreatedInD365()
                && static::currentUser()?->hasRole('commercial'))
            ->requiresConfirmation()
            ->modalDescription(fn (ItemCreationRequest $record) =>
                "Group: {$record->item_group} · Category: {$record->item_service_category} · ".
                ($record->is_stocked ? 'Stocked' : 'Non-stocked'))
            ->action(function (ItemCreationRequest $record) {
                $record->update([
                    'status' => 'creating',
                    'creation_triggered_by' => static::currentUser()?->id,
                    'creation_triggered_at' => now(),
                ]);

                CreateReleasedProductInD365::dispatch($record);
            });
    }

    public static function getViewErrorAction(): Action
    {
        return Action::make('viewError')
            ->label('View Error')
            ->icon('heroicon-o-exclamation-triangle')
            ->color('danger')
            ->visible(fn (?ItemCreationRequest $record) => $record?->status === 'create_failed' && filled($record->sync_error))
            ->modalWidth('4xl')
            ->schema([
                Forms\Components\Textarea::make('sync_error_display')
                    ->label('Sync Error')
                    ->default(fn (ItemCreationRequest $record) => $record->sync_error)
                    ->disabled()
                    ->rows(20)
                    ->extraInputAttributes(['style' => 'font-family: monospace; font-size: 12px;']),
            ])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close');
    }

    // Requester's path back in after a rejection: edit the core fields and
    // resend to Accounting. Only the original requester can trigger this.
    public static function getReviseAction(): Action
    {
        return Action::make('revise')
            ->label('Revise & Resubmit')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->visible(fn (?ItemCreationRequest $record) => $record?->canBeRevised()
                && $record->requested_by === static::currentUser()?->id)
            ->modalDescription(fn (ItemCreationRequest $record) => filled($record->rejection_reason)
                ? "Accounting's rejection reason: \"{$record->rejection_reason}\""
                : null)
            ->schema([
                Forms\Components\TextInput::make('item_name')
                    ->required()
                    ->maxLength(255)
                    ->default(fn (ItemCreationRequest $record) => $record->item_name),

                Forms\Components\Textarea::make('description')
                    ->default(fn (ItemCreationRequest $record) => $record->description),

                Forms\Components\TextInput::make('unit')
                    ->label('Unit of Measure')
                    ->required()
                    ->default(fn (ItemCreationRequest $record) => $record->unit),

                Forms\Components\Radio::make('is_used_in_project')
                    ->label('Used in a project?')
                    ->boolean()
                    ->inline()
                    ->default(fn (ItemCreationRequest $record) => $record->is_used_in_project),
            ])
            ->requiresConfirmation()
            ->action(function (ItemCreationRequest $record, array $data) {
                $record->update([
                    'item_name' => $data['item_name'],
                    'description' => $data['description'],
                    'unit' => $data['unit'],
                    'is_used_in_project' => $data['is_used_in_project'],
                    'status' => 'pending',
                    'rejection_reason' => null,
                ]);
            });
    }

    // Commercial can trigger D365 creation for several classified/failed
    // requests at once instead of one row at a time.
    public static function getBulkCreateInD365Action(): \Filament\Actions\BulkAction
    {
        return \Filament\Actions\BulkAction::make('bulkCreateInD365')
            ->label('Create in D365')
            ->icon('heroicon-o-cloud-arrow-up')
            ->color('success')
            ->visible(fn () => static::currentUser()?->hasRole('commercial'))
            ->requiresConfirmation()
            ->modalDescription('Queues a D365 creation job for every selected request that is classified or previously failed. Any other selected rows (wrong status) are skipped.')
            ->deselectRecordsAfterCompletion()
            ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                $eligible = $records->filter(fn (ItemCreationRequest $record) => $record->canBeCreatedInD365());
                $skipped = $records->count() - $eligible->count();

                foreach ($eligible as $record) {
                    $record->update([
                        'status' => 'creating',
                        'creation_triggered_by' => static::currentUser()?->id,
                        'creation_triggered_at' => now(),
                    ]);

                    CreateReleasedProductInD365::dispatch($record);
                }

                \Filament\Notifications\Notification::make()
                    ->title($eligible->count().' request(s) queued for D365 creation'.
                        ($skipped > 0 ? ", {$skipped} skipped (not eligible)" : ''))
                    ->success()
                    ->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListItemCreationRequests::route('/'),
            'create' => Pages\CreateItemCreationRequest::route('/create'),
            'view' => Pages\ViewItemCreationRequest::route('/{record}'),
            'edit' => Pages\EditItemCreationRequest::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\StatusLogsRelationManager::class,
        ];
    }

    // Plain requesters only see their own requests; Accounting and
    // Commercial need visibility across the whole queue to do their jobs.
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = static::currentUser();

        if ($user && ! $user->hasAnyRole(['accounting', 'commercial'])) {
            $query->where('requested_by', $user->id);
        }

        return $query;
    }
}
