<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MagicLink extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
        'used_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    /**
     * Get the user that owns the magic link.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a new magic link for a user
     */
    public static function generate(User $user, int $expiresInHours = 72): self
    {
        return self::create([
            'user_id' => $user->id,
            'token' => Str::random(64),
            'expires_at' => now()->addHours($expiresInHours),
        ]);
    }

    /**
     * Check if the magic link is valid
     */
    public function isValid(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }

    /**
     * Mark the magic link as used
     */
    public function markAsUsed(?string $ipAddress = null, ?string $userAgent = null): void
    {
        $this->update([
            'used_at' => now(),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }
}
