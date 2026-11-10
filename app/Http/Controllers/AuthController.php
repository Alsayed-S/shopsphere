<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Modules\Auth\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(private AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());

        return $this->createdResponse(
            'User registered successfully',
            (new UserResource($user))->resolve($request)
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $result = $this->authService->login($credentials);

        if (! $result) {
            return $this->errorResponse(
                'Invalid credentials',
                401
            );
        }

        return $this->successResponse(
            'User logged in successfully',
            [
                'user' => (new UserResource($result['user']))->resolve($request),
                'access_token' => $result['access_token'],
                'token_type' => 'Bearer',
            ]
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->successResponse(
            'User logged out successfully'
        );
    }
}
