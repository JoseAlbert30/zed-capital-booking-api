<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class DevUser extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the finance access records for the developer.
     */
    public function financeAccess(): HasMany
    {
        return $this->hasMany(FinanceAccess::class);
    }

    /**
     * Check if developer has access to a specific project.
     */
    public function hasProjectAccess(string $projectName): bool
    {
        return FinanceAccess::hasAccess($this->id, $projectName);
    }

    /**
     * Get all projects this developer has access to.
     */
    public function getAccessibleProjects()
    {
        return FinanceAccess::getUserProjects($this->id);
    }
}
