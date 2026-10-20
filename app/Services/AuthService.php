<?php

namespace App\Services;

use App\DTOs\LoginUserData;
use App\DTOs\RegisterUserData;
use App\Exceptions\InvalidCredentialsException;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

final class AuthService
{
    public function register(RegisterUserData $data): array
    {
        $user = User::query()->create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => Hash::make($data->password),
        ]);

        $token = Auth::guard('api')->login($user);

        return [$user, $token];
    }

    public function login(LoginUserData $data): array
    {
        $credentials = [
            'email' => $data->email,
            'password' => $data->password,
        ];

        if (! $token = Auth::guard('api')->attempt($credentials)) {
            throw new InvalidCredentialsException;
        }

        /** @var User $user */
        $user = Auth::guard('api')->user();

        return [$user, $token];
    }

    public function logout(): void
    {
        Auth::guard('api')->logout();
    }

    public function refresh(): string
    {
        return Auth::guard('api')->refresh();
    }
}
