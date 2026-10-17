<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresetMessageUsageEvent extends Model
{
    public const TYPE_COPIED = 'copied';

    public const TYPE_PASTED_BROWSER = 'pasted_browser';

    public const TYPE_SEARCH_NO_RESULTS = 'search_no_results';

    protected $fillable = [
        'user_id',
        'preset_message_id',
        'message_title',
        'event_type',
        'search_query',
        'context',
        'saved_seconds',
        'occurred_on',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'occurred_on' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function presetMessage(): BelongsTo
    {
        return $this->belongsTo(PresetMessage::class);
    }
}
