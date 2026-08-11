<?php

namespace App\Console\Commands;

use App\Models\D365ItemGroup;
use App\Services\D365ODataClient;
use Illuminate\Console\Command;

class SyncD365ItemGroups extends Command
{
    protected $signature = 'd365:sync-item-groups';

    protected $description = 'Sync InventItemGroups from D365 F&O into the local d365_item_groups table';

    public function handle(D365ODataClient $client): int
    {
        $this->info('Fetching item groups from D365...');

        try {
            $groups = $client->getEntitySet('InventItemGroupCDREntities', [
                '$select' => 'ItemGroupId,Name',
                '$filter' => "SysDataAreaId eq 'bp'"
            ]);
        } catch (\Throwable $e) {
            $this->error("Failed to fetch item groups: {$e->getMessage()}");
            return self::FAILURE;
        }

        $now = now();
        $count = 0;

        foreach ($groups as $group) {
            D365ItemGroup::updateOrCreate(
                ['item_group_id' => $group['ItemGroupId']],
                [
                    'description' => $group['Description'] ?? null,
                    'last_synced_at' => $now,
                ]
            );
            $count++;
        }

        $this->info("Synced {$count} item groups.");
        return self::SUCCESS;
    }
}
