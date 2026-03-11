<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceNote extends Model
{
    protected $table = 'finance_notes';

    protected $fillable = [
        'noteable_type',
        'noteable_id',
        'project_name',
        'message',
        'sent_by_type',
        'sent_by_id',
        'sent_by_name',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    protected $appends = ['attachments_data'];

    /**
     * Get the parent noteable model.
     */
    public function noteable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the attachments for this note.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(FinanceNoteAttachment::class, 'note_id');
    }

    /**
     * Append attachments with public URLs.
     */
    public function getAttachmentsDataAttribute(): array
    {
        return $this->attachments->map(function ($att) {
            return [
                'id'       => $att->id,
                'fileName' => $att->file_name,
                'fileUrl'  => $att->file_path,
                'fileType' => $att->file_type,
                'fileSize' => $att->file_size,
            ];
        })->toArray();
    }

    /**
     * Serialize for API response.
     */
    public function toApiArray(): array
    {
        return [
            'id'          => $this->id,
            'message'     => $this->message,
            'sentByType'  => $this->sent_by_type,
            'sentByName'  => $this->sent_by_name,
            'isRead'      => $this->is_read,
            'readAt'      => $this->read_at?->toIso8601String(),
            'createdAt'   => $this->created_at->toIso8601String(),
            'attachments' => $this->attachments_data,
        ];
    }
}
