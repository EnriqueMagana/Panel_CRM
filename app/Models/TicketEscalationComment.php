<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketEscalationComment extends Model
{
    protected $fillable = ['ticket_escalation_id', 'user_id', 'body'];

    public function escalation(): BelongsTo
    {
        return $this->belongsTo(TicketEscalation::class, 'ticket_escalation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketEscalationAttachment::class, 'ticket_escalation_comment_id');
    }
}
