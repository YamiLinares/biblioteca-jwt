<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());
        $token = Auth::guard('api')->login($user);

        return response()->json($this->tokenResponse($token) + ['user' => (new UserResource($user))->resolve()], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $token = Auth::guard('api')->attempt($request->validated());

        if (! $token) {
            return response()->json(['message' => 'Credenciales incorrectas.'], 401);
        }

        return response()->json($this->tokenResponse($token) + ['user' => (new UserResource(Auth::guard('api')->user()))->resolve()]);
    }

    public function me(): JsonResponse
    {
        return (new UserResource(Auth::guard('api')->user()))->response()->setStatusCode(200);
    }

    public function logout(): JsonResponse
    {
        Auth::guard('api')->logout();

        return response()->json(['message' => 'La sesión se cerró correctamente.']);
    }

    private function tokenResponse(string $token): array
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
        ];
    }
}
