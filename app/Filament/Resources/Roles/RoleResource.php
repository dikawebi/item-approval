<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages;
use BackedEnum;
use UnitEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Roles';

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    // Roles the app's logic depends on — block deleting these so the
    // Classify/Create-in-D365 gating never silently breaks.
    protected static array $protectedRoles = ['accounting', 'commercial'];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->label('Role name')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->helperText('Lowercase, no spaces recommended — e.g. "accounting", "commercial".'),

            Forms\Components\Hidden::make('guard_name')
                ->default('web'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (Role $record) => ! in_array($record->name, static::$protectedRoles))
                    ->requiresConfirmation(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
