<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bitácora de accesos: inicios y cierres de sesión, e intentos fallidos.
 */
class LoginEvent extends Model
{
    public const UPDATED_AT = null;

    public const EVENTS = ['login', 'logout', 'failed'];

    protected $fillable = ['user_id', 'event', 'email', 'ip_address', 'user_agent'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $event, ?User $user, ?string $email = null): void
    {
        $request = request();

        static::create([
            'user_id' => $user?->id,
            'event' => $event,
            'email' => $email ?? $user?->email,
            'ip_address' => $request?->ip(),
            'user_agent' => mb_substr((string) $request?->userAgent(), 0, 255) ?: null,
        ]);
    }
}
