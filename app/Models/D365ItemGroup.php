<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class D365ItemGroup extends Model
{
    protected $fillable = [
        'item_group_id',
        'description',
        'number_sequence_id',
        'default_item_model_group',
        'default_item_service_category',
        'last_synced_at',
    ];

    /**
     * Does this item group have a full default classification (both Item
     * Model Group and Item/Service Category) that the Classify & Assign
     * action can auto-fill and lock?
     */
    public function hasDefaultClassification(): bool
    {
        return filled($this->default_item_model_group)
            && filled($this->default_item_service_category);
    }

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public function numberSequence(): BelongsTo
    {
        return $this->belongsTo(NumberSequence::class);
    }

    /**
     * Options array for Filament Select: item_group_id => "GROUPID - Description"
     */
    public static function selectOptions(): array
    {
        return static::query()
            ->orderBy('item_group_id', 'asc')
            ->get()
            ->mapWithKeys(fn (D365ItemGroup $group) => [
                $group->item_group_id => $group->description
                    ? "{$group->item_group_id} - {$group->description}"
                    : $group->item_group_id,
            ])
            ->toArray();
    }
}
