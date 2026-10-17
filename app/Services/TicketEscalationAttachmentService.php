<?php

namespace App\Services;

use App\Models\TicketEscalation;
use App\Models\TicketEscalationAttachment;
use App\Models\TicketEscalationComment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class TicketEscalationAttachmentService
{
    /** @param array<int, UploadedFile> $files */
    public function storeMany(
        TicketEscalation $escalation,
        User $uploader,
        array $files,
        ?TicketEscalationComment $comment = null,
    ): void {
        $storedPaths = [];

        try {
            foreach ($files as $file) {
                $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
                $name = Str::uuid().'.'.$extension;
                $path = $file->storeAs("ticket-escalations/{$escalation->id}", $name, 'public');
                throw_if(! $path, \RuntimeException::class, 'No fue posible guardar el archivo.');
                $storedPaths[] = $path;

                TicketEscalationAttachment::query()->create([
                    'ticket_escalation_id' => $escalation->id,
                    'ticket_escalation_comment_id' => $comment?->id,
                    'uploaded_by' => $uploader->id,
                    'path' => $path,
                    'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'size' => $file->getSize(),
                ]);
            }
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($storedPaths);
            TicketEscalationAttachment::query()->whereIn('path', $storedPaths)->delete();
            throw $exception;
        }
    }
}
