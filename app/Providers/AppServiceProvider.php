<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::viaRequest('sanctum', function (Request $request) {
            $token = PersonalAccessToken::findToken($request->bearerToken());

            if (! $token || $token->isExpired()) {
                return null;
            }

            $token->forceFill(['last_used_at' => now()])->save();

            return $token->tokenable;
        });
    }
}
