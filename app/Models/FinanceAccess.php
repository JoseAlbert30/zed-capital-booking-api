<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceAccess extends Model
{
    protected $table = 'finance_access';

    protected $fillable = [
        'dev_user_id',
        'project_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the developer user that owns the finance access
     */
    public function devUser(): BelongsTo
    {
        return $this->belongsTo(DevUser::class);
    }

    /**
     * Get the property/project for this access
     */
    public function property()
    {
        return Property::where('project_name', $this->project_name)->first();
    }

    /**
     * Check if a developer has access to a project
     */
    public static function hasAccess(int $devUserId, string $projectName): bool
    {
        return self::where('dev_user_id', $devUserId)
            ->where('project_name', $projectName)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get all projects a developer has access to
     */
    public static function getUserProjects(int $devUserId): array
    {
        return self::where('dev_user_id', $devUserId)
            ->where('is_active', true)
            ->pluck('project_name')
            ->toArray();
    }

    /**
     * Grant access to a developer for a project
     */
    public static function grantAccess(int $devUserId, string $projectName): self
    {
        return self::updateOrCreate(
            [
                'dev_user_id' => $devUserId,
                'project_name' => $projectName,
            ],
            [
                'is_active' => true,
            ]
        );
    }

    /**
     * Revoke access from a developer for a project
     */
    public static function revokeAccess(int $devUserId, string $projectName): bool
    {
        return self::where('dev_user_id', $devUserId)
            ->where('project_name', $projectName)
            ->update(['is_active' => false]);
    }
}
