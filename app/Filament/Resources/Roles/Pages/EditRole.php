<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Support\Roles;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => ! in_array($this->record->name, Roles::PROTECTED, true)),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Server-side guard: core role names drive the workflow gating,
        // so silently revert any rename attempt on them.
        if (in_array($this->record->name, Roles::PROTECTED, true)) {
            $data['name'] = $this->record->name;
        }

        return $data;
    }
}
