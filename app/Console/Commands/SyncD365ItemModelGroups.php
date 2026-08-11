<?php

namespace App\Console\Commands;

use App\Models\D365ItemModelGroup;
use App\Services\D365ODataClient;
use Illuminate\Console\Command;

class SyncD365ItemModelGroups extends Command
{
    protected $signature = 'd365:sync-item-model-groups';

    protected $description = 'Sync item model groups from D365 F&O into the local lookup table';

    public function handle(D365ODataClient $client): int
    {
        $this->info('Fetching item model groups from D365...');

        try {
            // NOTE: entity set name is unverified — confirm against your own
            // $metadata. Likely candidates: "InventoryModelGroups" or an
            // entity with a "CDR" suffix, matching InventItemGroupCDREntities.
            $groups = $client->getEntitySet('InventModelGroupCDREntities', [
                '$select' => 'ModelGroupId,Name',
                '$filter' => "SysDataAreaId eq 'bp'"
            ]);
        } catch (\Throwable $e) {
            $this->error("Failed to fetch item model groups: {$e->getMessage()}");
            return self::FAILURE;
        }

        $now = now();
        $count = 0;

        foreach ($groups as $group) {
            D365ItemModelGroup::updateOrCreate(
                ['item_model_group_id' => $group['ModelGroupId']],
                [
                    'description' => $group['Name'] ?? null,
                    'last_synced_at' => $now,
                ]
            );
            $count++;
        }

        $this->info("Synced {$count} item model groups.");
        return self::SUCCESS;
    }
}
