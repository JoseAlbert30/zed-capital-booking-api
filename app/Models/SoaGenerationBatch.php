<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoaGenerationBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'total_soas',
        'generated_count',
        'failed_count',
        'status',
        'unit_ids',
        'initiated_by',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'unit_ids' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Increment generated count
     */
    public function incrementGenerated()
    {
        $this->increment('generated_count');
        $this->checkIfComplete();
    }

    /**
     * Increment failed count
     */
    public function incrementFailed()
    {
        $this->increment('failed_count');
        $this->checkIfComplete();
    }

    /**
     * Check if batch is complete and update status
     */
    protected function checkIfComplete()
    {
        $this->refresh();
        
        if (($this->generated_count + $this->failed_count) >= $this->total_soas) {
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
        if ($this->total_soas === 0) {
            return 0;
        }
        
        return round((($this->generated_count + $this->failed_count) / $this->total_soas) * 100, 2);
    }
}
