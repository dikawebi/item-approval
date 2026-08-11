<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class D365ItemModelGroup extends Model
{
    protected $fillable = [
        'item_model_group_id',
        'description',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public static function selectOptions(): array
    {
        return static::query()
            ->orderBy('item_model_group_id', 'asc')
            ->get()
            ->mapWithKeys(fn (self $group) => [
                $group->item_model_group_id => $group->description
                    ? "{$group->item_model_group_id} - {$group->description}"
                    : $group->item_model_group_id,
            ])
            ->toArray();
    }
}
