<?php

namespace App\Jobs;

use App\Models\D365ItemGroup;
use App\Models\ItemCreationRequest;
use App\Models\NumberSequence;
use App\Models\User;
use App\Services\D365ODataClient;
use App\Notifications\ItemWorkflowNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateReleasedProductInD365 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    protected const DEFAULT_SEQUENCE_CODE = 'item_number';

    public function __construct(
        public ItemCreationRequest $request
    ) {}

    public function handle(D365ODataClient $client): void
    {
        if (! $this->request->isFullyClassified()) {
            $this->request->update([
                'status' => 'create_failed',
                'sync_error' => 'Missing item group, item model group, item/service category, or stocked flag — cannot create without full accounting classification.',
            ]);
            return;
        }

        if (empty($this->request->assigned_item_number)) {
            // Resolve sequence: use the item group's dedicated sequence if set,
            // otherwise fall back to the global 'item_number' sequence.
            $itemGroup = D365ItemGroup::where('item_group_id', $this->request->item_group)->first();
            $sequenceCode = optional($itemGroup->numberSequence ?? null)->code ?? self::DEFAULT_SEQUENCE_CODE;

            try {
                $itemNumber = NumberSequence::next($sequenceCode);
            } catch (\Throwable $e) {
                $this->request->update([
                    'status'     => 'create_failed',
                    'sync_error' => $e->getMessage(),
                ]);
                return;
            }

            $this->request->update(['assigned_item_number' => $itemNumber]);
        }

        try {
            $result = $client->createEntity('ReleasedProductCreationsV2', [
                'dataAreaId' => 'bp',
                'ItemNumber' => $this->request->assigned_item_number,
                'ProductNumber' => $this->request->assigned_item_number,
                'ProductName' => $this->request->item_name,
                'ProductSearchName' => $this->request->item_name,
                //'ProductDescription' => $this->request->description,
                'ProductType' => 'Item',
                "ProductSubType" => 'Product',
                'ProductGroupId' => $this->request->item_group,
                'ItemModelGroupId' => $this->request->item_model_group,
                'InventoryUnitSymbol' => $this->request->unit,
                'PurchaseUnitSymbol' => $this->request->unit,
                'SalesUnitSymbol' => $this->request->unit,
                'BOMUnitSymbol' => $this->request->unit,
                //'ProcurementCategoryId' => $this->request->item_service_category,
                //'IsStockedProduct' => $this->request->is_stocked,
                'TrackingDimensionGroupName' => 'None',
                'VariantConfigurationTechnology' => 'None',
                //'ProductSubType' => 'Product',
                //'IsUsedInProject' => $this->request->is_used_in_project,
            ]);

            $this->request->update([
                'status'        => 'created',
                'synced_to_d365' => true,
                'd365_item_id'  => $result['ItemNumber'] ?? $this->request->assigned_item_number,
                'synced_at'     => now(),
                'sync_error'    => null,
            ]);

            $this->sendSuccessNotifications();
        } catch (\Throwable $e) {
            $this->request->update([
                'status'     => 'create_failed',
                'sync_error' => $e->getMessage(),
            ]);

            $this->sendFailureNotification($e->getMessage());

            throw $e;
        }
    }

    protected function sendSuccessNotifications(): void
    {
        $title = "✅ Item created in D365: {$this->request->item_name}";
        $body = "The item \"{$this->request->item_name}\" was successfully created with item number {$this->request->assigned_item_number}.";
        $url = route('filament.item-approval.resources.item-creation-requests.edit', $this->request);

        // Notify Commercial (who triggered it)
        if ($this->request->creation_triggered_by) {
            $triggerUser = User::find($this->request->creation_triggered_by);
            if ($triggerUser) {
                ItemWorkflowNotifier::send($triggerUser, $title, $body, $url, 'View Item', 'heroicon-o-check-circle', 'success');
            }
        }

        // Notify Requester (if different)
        if ($this->request->requested_by && $this->request->requested_by !== $this->request->creation_triggered_by) {
            $requester = User::find($this->request->requested_by);
            if ($requester) {
                ItemWorkflowNotifier::send($requester, $title, $body, $url, 'View Item', 'heroicon-o-check-circle', 'success');
            }
        }
    }

    protected function sendFailureNotification(string $error): void
    {
        $title = "❌ D365 creation failed: {$this->request->item_name}";
        $body = "Creation failed for \"{$this->request->item_name}\". Error: " . \Illuminate\Support\Str::limit($error, 200);
        $url = route('filament.item-approval.resources.item-creation-requests.edit', $this->request);

        if ($this->request->creation_triggered_by) {
            $triggerUser = User::find($this->request->creation_triggered_by);
            if ($triggerUser) {
                ItemWorkflowNotifier::send($triggerUser, $title, $body, $url, 'View Request / Retry', 'heroicon-o-exclamation-triangle', 'danger');
            }
        }
    }
}
