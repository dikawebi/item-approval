<?php

namespace App\Filament\Resources\NumberSequences;

use App\Filament\Resources\NumberSequences\Pages;
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

class NumberSequenceResource extends Resource
{
    protected static ?string $model = NumberSequence::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-hashtag';

    protected static ?string $navigationLabel = 'Number Sequences';

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('code')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->helperText('Internal key the app uses to look this sequence up, e.g. "item_number". Don\'t change this once it\'s wired into code.'),

            Forms\Components\TextInput::make('label')
                ->maxLength(255)
                ->helperText('Friendly name shown in this list, e.g. "Item Number".'),

            Forms\Components\TextInput::make('prefix')
                ->maxLength(50)
                ->helperText('Optional — e.g. "ITM-"'),

            Forms\Components\TextInput::make('suffix')
                ->maxLength(50),

            Forms\Components\TextInput::make('next_number')
                ->numeric()
                ->required()
                ->minValue(1)
                ->helperText(
                    'The next number to be issued. Increments once per item-creation request — even on D365 retries, the same reserved number is reused (no double-increment). '."\n"
                    .'Only change this manually if you need to skip ahead or correct a mistake.'
                ),

            Forms\Components\TextInput::make('padding_length')
                ->numeric()
                ->required()
                ->minValue(1)
                ->maxValue(20)
                ->default(6)
                ->helperText('How many digits to pad to — 6 means next_number 42 becomes "000042".'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('preview')
                    ->label('Next up')
                    ->state(fn (NumberSequence $record) => $record->previewNext())
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('next_number')
                    ->label('Raw next # (editable)')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('used_by_item_groups')
                    ->label('Used by item groups')
                    ->getStateUsing(fn (NumberSequence $record) =>
                        D365ItemGroup::where('number_sequence_id', $record->id)
                            ->pluck('item_group_id')
                            ->join(', ') ?: 'None'
                    )
                    ->placeholder('None'),

                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->requiresConfirmation(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNumberSequences::route('/'),
            'create' => Pages\CreateNumberSequence::route('/create'),
            'edit' => Pages\EditNumberSequence::route('/{record}/edit'),
        ];
    }
}
