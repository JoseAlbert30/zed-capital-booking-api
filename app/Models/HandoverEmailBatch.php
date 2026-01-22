<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HandoverEmailBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'total_emails',
        'sent_count',
        'failed_count',
        'status',
        'unit_ids',
        'failed_unit_ids',
        'initiated_by',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'unit_ids' => 'array',
        'failed_unit_ids' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Increment sent count
     */
    public function incrementSent()
    {
        $this->increment('sent_count');
        $this->checkIfComplete();
    }

    /**
     * Increment failed count and track failed unit ID
     */
    public function incrementFailed($unitId = null)
    {
        $this->increment('failed_count');
        
        // Track failed unit ID
        if ($unitId) {
            $failedUnits = $this->failed_unit_ids ?? [];
            if (!in_array($unitId, $failedUnits)) {
                $failedUnits[] = $unitId;
                $this->update(['failed_unit_ids' => $failedUnits]);
            }
        }
        
        $this->checkIfComplete();
    }

    /**
     * Check if batch is complete and update status
     */
    protected function checkIfComplete()
    {
        $this->refresh();
        
        if (($this->sent_count + $this->failed_count) >= $this->total_emails) {
            $this->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);
        }
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentage()
    {
        if ($this->total_emails === 0) {
            return 0;
        }
        
        return round((($this->sent_count + $this->failed_count) / $this->total_emails) * 100, 2);
    }
}
