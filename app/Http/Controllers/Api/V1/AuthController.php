<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\LogoutRequest;
use App\Http\Requests\Auth\MeRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\AuthTokenResource;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        return $this->success(
            new AuthTokenResource($this->authService->login($request->validated())),
            'Login successful.'
        );
    }

    public function logout(LogoutRequest $request): JsonResponse
    {
        $this->authService->logout($request->bearerToken());

        return $this->success(null, 'Logout successful.');
    }

    public function me(MeRequest $request): JsonResponse
    {
        return $this->success(new UserResource($request->user()), 'Authenticated user retrieved.');
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $message = $this->authService->sendPasswordResetLink($request->validated('email'));

        return $this->success(null, $message);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $message = $this->authService->resetPassword($request->validated());

        return $this->success(null, $message);
    }
}
