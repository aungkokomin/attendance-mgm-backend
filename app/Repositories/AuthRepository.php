<?php

namespace App\Repositories;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Support\Facades\Password;

class AuthRepository
{
    public function findActiveUserByEmail(string $email): ?User
    {
        return User::query()
            ->where('email', $email)
            ->where('status', 'active')
            ->first();
    }

    /**
     * @return array{user: User, token: string}
     */
    public function createAccessToken(User $user): array
    {
        return [
            'user' => $user,
            'token' => PersonalAccessToken::createTokenFor($user),
        ];
    }

    public function deleteAccessToken(?string $plainTextToken): bool
    {
        $token = PersonalAccessToken::findToken($plainTextToken);

        return $token ? (bool) $token->delete() : false;
    }

    public function sendPasswordResetLink(array $credentials): string
    {
        return Password::sendResetLink($credentials);
    }

    public function resetPassword(array $credentials, callable $callback): string
    {
        return Password::reset($credentials, $callback);
    }
}
