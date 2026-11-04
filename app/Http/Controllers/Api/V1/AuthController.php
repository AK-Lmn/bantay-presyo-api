<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Group;

#[Group('Auth', 'Register, log in and log out. Tokens are Sanctum personal access tokens sent as `Authorization: Bearer <token>`.')]
class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create($request->safe()->only(['name', 'email', 'password']));

        return $this->tokenResponse($user, $request->input('device_name', 'api'), 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->string('email'))->first();

        if ($user === null || ! Hash::check($request->string('password')->value(), $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        return $this->tokenResponse($user, $request->input('device_name', 'api'));
    }

    #[Authenticated]
    public function logout(Request $request): Response
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->noContent();
    }

    private function tokenResponse(User $user, string $deviceName, int $status = 200): JsonResponse
    {
        return $this->data([
            'user' => UserResource::make($user)->resolve(),
            'token' => $user->createToken($deviceName)->plainTextToken,
            'token_type' => 'Bearer',
        ], $status);
    }
}
