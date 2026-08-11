<?php

namespace Database\Seeders;

use App\Models\D365ItemGroup;
use App\Models\D365ItemModelGroup;
use Illuminate\Database\Seeder;

class D365ItemGroupClassificationSeeder extends Seeder
{
    /**
     * Source: "Panduan Pembuatan Item Product.xlsx" — sheet "Item Grup".
     *
     * Format: [item_group_id, description, default_item_model_group, default_item_service_category]
     *
     * default_item_model_group here is the 3-way tag used company-wide
     * (Service / Inventory / WA_DATE) — per Andika, the majority of items
     * use these three, so they're the real values (not placeholders).
     */
    protected const MAPPING = [
            ['AUC', 'Asset Under Construction', 'Service', 'service'],
            ['BARGE FUEL', 'Barge Fuel', 'Service', 'service'],
            ['BARGE RENT', 'Barge Loading Conveyor Rent', 'Service', 'service'],
            ['BUILD RENT', 'Building Rent', 'Service', 'service'],
            ['CATERING', 'Catering', 'Service', 'service'],
            ['CHANEL FEE', 'Channel Fee', 'Service', 'service'],
            ['CON SPAR', 'Consume Sparepart', 'Service', 'service'],
            ['CONSIGN', 'Consignment', 'Inventory', 'item'],
            ['CONSUL FEE', 'Consultant Fee', 'Service', 'service'],
            ['CONSUME', 'Store', 'WA_DATE', 'item'],
            ['CONTR FEE', 'Contractor Fee', 'Service', 'service'],
            ['EQUIP RENT', 'Equipment Rent', 'Service', 'service'],
            ['EXPLOSIVE', 'Inventory Explosive Material', 'WA_DATE', 'item'],
            ['EX-TRANS', 'Transport Expense Fuel', 'Service', 'service'],
            ['FUEL', 'Inventory Fuel', 'WA_DATE', 'item'],
            ['ISP RENT', 'ISP Rent', 'Service', 'service'],
            ['IT CONSUM', 'IT Consumables', 'WA_DATE', 'item'],
            ['LABOUR EXP', 'Labour Outsourcing Expense', 'Service', 'service'],
            ['LAND ACQ', 'Land Acquisition', 'Service', 'service'],
            ['LOCAL TAX', 'LOCAL TAX', 'Service', 'service'],
            ['LUBRICANT', 'Lubricant', 'WA_DATE', 'item'],
            ['MATERAI', 'Materai', 'Service', 'service'],
            ['NETWORK', 'Network', 'Service', 'service'],
            ['OFF EXP', 'Office Expense', 'Service', 'service'],
            ['OFFSUPP EXP', 'Consumable Office Supplies', 'Service', 'service'],
            ['PPH22', 'PPH22 (0.3%)', 'Service', 'service'],
            ['PRE PERMIT', 'Prepaid Permit & Licences', 'Service', 'service'],
            ['QUARRY', 'Quarry', 'Service', 'service'],
            ['R&M SERV', 'Repair & Maintenance-Service', 'Service', 'service'],
            ['SAMPLING', 'Coal Sampling Analysis Expense', 'Service', 'service'],
            ['SECURITY', 'Security Expenses', 'Service', 'service'],
            ['SOFTWARE', 'SOFTWARE & LICENSE', 'Service', 'service'],
            ['SPAREPART', 'Inventory Sparepart', 'WA_DATE', 'item'],
            ['STORE EX', 'Store Expense', 'Service', 'service'],
            ['SURVEY EXP', 'Survey Expenses - Production', 'Service', 'service'],
            ['TRAN HEAVY', 'Transport Expense Heavy Equipment', 'Service', 'service'],
            ['TRAN OTH', 'Transport Expense Other Material', 'Service', 'service'],
            ['TRANS SPAR', 'Transport Expense Sparepart', 'Service', 'service'],
            ['TRAVEL DOM', 'Travel Expense Domestic', 'Service', 'service'],
            ['TRAVEL INT', 'Travel Expense International', 'Service', 'service'],
            ['TUG HIRE', 'Tug and Barge Hire', 'Service', 'service'],
            ['TYRE', 'Tyre', 'WA_DATE', 'item'],
            ['VEHIC RENT', 'Vehicle Rent', 'Service', 'service'],
            ['WASH RENT', 'Wash Plant Rent', 'Service', 'service'],
            ['EXTRACOMP', 'Extracomptable', 'Service', 'service'],
            ['LICENSE', 'License & Permit', 'Service', 'service'],
    ];

    public function run(): void
    {
        // Make sure the 3 model groups referenced above exist as selectable
        // options, without disturbing any that already got synced in from
        // real D365 metadata.
        foreach (['Service', 'Inventory', 'WA_DATE'] as $modelGroupId) {
            D365ItemModelGroup::firstOrCreate(['item_model_group_id' => $modelGroupId]);
        }

        foreach (self::MAPPING as [$itemGroupId, $description, $modelGroup, $category]) {
            $group = D365ItemGroup::firstOrNew(['item_group_id' => $itemGroupId]);

            // Don't clobber a description already synced from D365 — only
            // fill it if this is a brand-new row.
            if (! $group->exists) {
                $group->description = $description;
            }

            $group->default_item_model_group = $modelGroup;
            $group->default_item_service_category = $category;
            $group->save();
        }

        $this->command?->info('Seeded default classification for ' . count(self::MAPPING) . ' item groups.');
    }
}
