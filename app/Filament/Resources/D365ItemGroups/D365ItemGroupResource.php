<?php

namespace App\Filament\Resources\D365ItemGroups;

use App\Filament\Resources\D365ItemGroups\Pages;
use App\Models\D365ItemGroup;
use App\Models\NumberSequence;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class D365ItemGroupResource extends Resource
{
    protected static ?string $model = D365ItemGroup::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Item Groups';

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('item_group_id')
                ->label('Item Group ID')
                ->required()
                ->maxLength(255)
                ->helperText('The ID as it appears in D365, e.g. "TOOLS".'),

            Forms\Components\TextInput::make('description')
                ->maxLength(255),

            Forms\Components\Select::make('number_sequence_id')
                ->label('Number Sequence')
                ->options(fn () => NumberSequence::orderBy('label')->get()->mapWithKeys(
                    fn (NumberSequence $seq) => [
                        $seq->id => ($seq->label ?: $seq->code)
                            . ' — next: ' . $seq->previewNext(),
                    ]
                ))
                ->searchable()
                ->nullable()
                ->placeholder('Use global default (item_number)')
                ->helperText('When set, item creation requests assigned to this group will use this sequence. Leave blank to use the global "item_number" sequence.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item_group_id')
                    ->label('Item Group ID')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('numberSequence.label')
                    ->label('Number Sequence')
                    ->placeholder('Global default')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('last_synced_at')
                    ->label('Last Synced')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never'),
            ])
            ->defaultSort('item_group_id')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->requiresConfirmation(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListD365ItemGroups::route('/'),
            'create' => Pages\CreateD365ItemGroup::route('/create'),
            'edit'   => Pages\EditD365ItemGroup::route('/{record}/edit'),
        ];
    }
}
