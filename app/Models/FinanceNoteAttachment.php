<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceNoteAttachment extends Model
{
    protected $table = 'finance_note_attachments';

    protected $fillable = [
        'note_id',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
    ];
}
