<?php

namespace App\Filament\Resources\ItemCreationRequests\Pages;

use App\Filament\Resources\ItemCreationRequests\ItemCreationRequestResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditItemCreationRequest extends EditRecord
{
    protected static string $resource = ItemCreationRequestResource::class;

    /**
     * These mirror the row actions defined in ItemCreationRequestResource::table().
     * Notification/email links point to this edit page, so the actual workflow
     * buttons need to live here too — otherwise a user following the email link
     * lands on a page with nothing to click.
     */
    protected function getHeaderActions(): array
    {
        $actions = [
            ItemCreationRequestResource::getClassifyAction(),
            ItemCreationRequestResource::getNeedsInfoAction(),
            ItemCreationRequestResource::getRejectAction(),
            ItemCreationRequestResource::getCreateInD365Action(),
            ItemCreationRequestResource::getReviseAction(),
            ItemCreationRequestResource::getViewErrorAction(),
        ];

        if ($this->record->status === 'needs_info') {
            $actions[] = Action::make('respondToAccounting')
                ->label('Respond to Accounting')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('warning')
                ->requiresConfirmation()
                ->action(fn () => $this->save());
        }

        return $actions;
    }

    /**
     * Revisions from rejected or needs_info requests should re-enter queue.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (in_array($this->record->status, ['rejected', 'needs_info'], true)) {
            $data['status'] = 'pending';
        }

        if ($this->record->status === 'needs_info') {
            $data['info_request_note'] = $this->record->info_request_note;
            $data['requester_response_note'] = $data['requester_response_note'] ?? $this->record->requester_response_note;
        }

        return $data;
    }
}

