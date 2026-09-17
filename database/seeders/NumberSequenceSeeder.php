<?php

namespace Database\Seeders;

use App\Models\D365ItemGroup;
use App\Models\NumberSequence;
use Illuminate\Database\Seeder;

class NumberSequenceSeeder extends Seeder
{
    /**
     * Fallback sequence used by CreateReleasedProductInD365 when the item's
     * group has no dedicated sequence (see DEFAULT_SEQUENCE_CODE there).
     * Padding 6 with no prefix — every group in the master gets its own
     * prefixed sequence below.
     */
    protected const FALLBACK = [
        'code' => 'item_number',
        'label' => 'D365 item number (fallback)',
        'prefix' => '',
        'suffix' => '',
        'next_number' => 1,
        'padding_length' => 6,
    ];

    /**
     * Manual prefix overrides: [item_group_id => prefix, ...].
     * Wins over the auto-derived short code when present.
     */
    protected const PREFIX_OVERRIDES = [
        // e.g. 'SPAREPART' => 'SP-',
    ];

    public function run(): void
    {
        NumberSequence::firstOrCreate(
            ['code' => self::FALLBACK['code']],
            self::FALLBACK
        );

        $used = [];
        $linked = 0;

        foreach (D365ItemGroup::orderBy('item_group_id')->get() as $group) {
            $prefix = self::PREFIX_OVERRIDES[$group->item_group_id]
                ?? $this->derivePrefix($group->item_group_id, $used);

            $sequence = NumberSequence::firstOrCreate(
                ['code' => $this->sequenceCodeFor($group->item_group_id)],
                [
                    'label' => "D365 item number ({$group->item_group_id})",
                    'prefix' => $prefix,
                    'suffix' => '',
                    'next_number' => 1,
                    'padding_length' => 6,
                ]
            );

            // Only link when the group has no sequence yet — never clobber a
            // sequence assigned manually via the UI.
            if ($group->number_sequence_id === null) {
                $group->update(['number_sequence_id' => $sequence->id]);
                $linked++;
            }
        }

        $this->command?->info(
            "Seeded fallback 'item_number' sequence + linked {$linked} item group(s) to dedicated sequences."
        );
    }

    /**
     * Short code from the master group code: first letter of each word
     * (SPAREPART → S-, BARGE FUEL → BF-). On collision, the first word is
     * extended letter by letter until unique (BARGE RENT → BR-, BUILD RENT
     * → BUR-). Deterministic as long as group codes are processed sorted.
     */
    protected function derivePrefix(string $itemGroupId, array &$used): string
    {
        $tokens = preg_split('/[^A-Za-z0-9]+/', strtoupper($itemGroupId), -1, PREG_SPLIT_NO_EMPTY);

        $initials = implode('', array_map(fn ($t) => $t[0], $tokens));

        $candidates = [$initials];
        $first = $tokens[0];
        $rest = implode('', array_map(fn ($t) => $t[0], array_slice($tokens, 1)));
        for ($len = 2; $len <= strlen($first); $len++) {
            $candidates[] = substr($first, 0, $len).$rest;
        }
        $candidates[] = implode('', $tokens);

        foreach ($candidates as $candidate) {
            if (! isset($used[$candidate])) {
                $used[$candidate] = true;

                return $candidate.'-';
            }
        }

        $i = 2;
        while (isset($used[$initials.$i])) {
            $i++;
        }
        $used[$initials.$i] = true;

        return $initials.$i.'-';
    }

    protected function sequenceCodeFor(string $itemGroupId): string
    {
        $slug = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '_', $itemGroupId));

        return "item_number_{$slug}";
    }
}
