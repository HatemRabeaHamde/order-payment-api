<?php

namespace App\Http\Controllers\Api;

use App\DTOs\LoginUserData;
use App\DTOs\RegisterUserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        [$user, $token] = $this->authService->register(
            RegisterUserData::fromRequest($request->validated())
        );

        return $this->success(
            data: $this->withToken($user, $token),
            message: 'User registered successfully.',
            status: 201,
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        [$user, $token] = $this->authService->login(
            LoginUserData::fromRequest($request->validated())
        );

        return $this->success(
            data: $this->withToken($user, $token),
            message: 'Login successful.',
        );
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return $this->success(message: 'Logged out successfully.');
    }

    public function refresh(): JsonResponse
    {
        $token = $this->authService->refresh();

        return $this->success(data: ['access_token' => $token, 'token_type' => 'bearer']);
    }

    public function me(): JsonResponse
    {
        return $this->success(data: new UserResource(Auth::guard('api')->user()));
    }

    /**
     * @return array<string, mixed>
     */
    private function withToken(User $user, string $token): array
    {
        return [
            'user' => new UserResource($user),
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
        ];
    }
}
