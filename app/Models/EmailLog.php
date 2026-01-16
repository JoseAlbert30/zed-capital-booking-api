<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    protected $fillable = [
        'user_id',
        'recipient_email',
        'recipient_name',
        'subject',
        'message',
        'email_type',
        'status',
        'error_message',
        'metadata',
        'sent_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
    ];

    /**
     * Get the user that received the email
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
