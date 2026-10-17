<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class TicketEscalation extends Model
{
    use HasFactory;

    public const STATUSES = [
        'pending' => 'Pendiente',
        'in_progress' => 'En seguimiento',
        'waiting' => 'En espera',
        'resolved' => 'Resuelto',
        'cancelled' => 'Cancelado',
    ];

    protected $fillable = [
        'created_by',
        'folio',
        'title',
        'escalated_area',
        'description',
        'status',
        'escalated_at',
    ];

    protected function casts(): array
    {
        return ['escalated_at' => 'date'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketEscalationComment::class)->oldest();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketEscalationAttachment::class);
    }

    public function ticketAttachments(): HasMany
    {
        return $this->attachments()->whereNull('ticket_escalation_comment_id');
    }

    protected static function booted(): void
    {
        static::deleting(function (TicketEscalation $escalation): void {
            Storage::disk('public')->delete($escalation->attachments()->pluck('path')->all());
        });
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
