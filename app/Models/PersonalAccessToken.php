<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class PersonalAccessToken extends Model
{
    protected $fillable = [
        'name',
        'token',
        'abilities',
        'last_used_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public static function createTokenFor(User $user, string $name = 'auth-token', array $abilities = ['*']): string
    {
        $plainTextToken = Str::random(40);

        $token = $user->tokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plainTextToken),
            'abilities' => $abilities,
        ]);

        return $token->getKey().'|'.$plainTextToken;
    }

    public static function findToken(?string $plainTextToken): ?self
    {
        if (! $plainTextToken) {
            return null;
        }

        [$id, $token] = str_contains($plainTextToken, '|')
            ? explode('|', $plainTextToken, 2)
            : [null, $plainTextToken];

        $accessToken = $id
            ? self::query()->find($id)
            : self::query()->where('token', hash('sha256', $token))->first();

        if (! $accessToken) {
            return null;
        }

        return hash_equals($accessToken->token, hash('sha256', $token)) ? $accessToken : null;
    }

    public function tokenable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
