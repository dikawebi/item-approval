<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemCreationRequestStatusLog extends Model
{
    protected $fillable = [
        'item_creation_request_id',
        'from_status',
        'to_status',
        'user_id',
        'note',
        'requester_response_note',
    ];

    public function itemCreationRequest(): BelongsTo
    {
        return $this->belongsTo(ItemCreationRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
