<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrowserExtensionConnection extends Model
{
    protected $fillable = ['user_id', 'browser_name', 'connected_at', 'last_seen_at'];

    protected function casts(): array
    {
        return ['connected_at' => 'datetime', 'last_seen_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
