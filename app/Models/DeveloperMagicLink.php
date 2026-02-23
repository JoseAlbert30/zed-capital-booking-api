<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DeveloperMagicLink extends Model
{
    protected $fillable = [
        'project_name',
        'developer_email',
        'developer_name',
        'token',
        'password',
        'password_set',
        'password_set_at',
        'expires_at',
        'first_used_at',
        'last_used_at',
        'access_count',
        'ip_address',
        'user_agent',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'first_used_at' => 'datetime',
        'last_used_at' => 'datetime',
        'password_set' => 'boolean',
        'password_set_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Generate a new magic link for a developer
     */
    public static function generate(string $projectName, string $developerEmail, ?string $developerName = null, int $expiresInDays = 90): self
    {
        // Deactivate any existing active links for this project/developer
        self::where('project_name', $projectName)
            ->where('developer_email', $developerEmail)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        return self::create([
            'project_name' => $projectName,
            'developer_email' => $developerEmail,
            'developer_name' => $developerName,
            'token' => Str::random(64),
            'expires_at' => now()->addDays($expiresInDays),
            'is_active' => true,
        ]);
    }

    /**
     * Check if the magic link is valid
     */
    public function isValid(): bool
    {
        return $this->is_active && 
               $this->expires_at->isFuture();
    }

    /**
     * Mark the link as used
     */
    public function markAsUsed(string $ipAddress = null, string $userAgent = null): void
    {
        $this->increment('access_count');
        
        if (!$this->first_used_at) {
            $this->first_used_at = now();
        }
        
        $this->last_used_at = now();
        $this->ip_address = $ipAddress;
        $this->user_agent = $userAgent;
        $this->save();
    }

    /**
     * Deactivate the link
     */
    public function deactivate(): void
    {
        $this->is_active = false;
        $this->save();
    }

    /**
     * Set password for developer
     */
    public function setPassword(string $password): void
    {
        $this->password = bcrypt($password);
        $this->password_set = true;
        $this->password_set_at = now();
        $this->save();
    }

    /**
     * Check if password matches
     */
    public function checkPassword(string $password): bool
    {
        if (!$this->password) {
            return false;
        }
        return \Hash::check($password, $this->password);
    }

    /**
     * Check if this is first time access
     */
    public function isFirstAccess(): bool
    {
        return !$this->first_used_at;
    }
}
