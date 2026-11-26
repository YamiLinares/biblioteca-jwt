<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthenticateJwt
{
    public function handle(Request $request, Closure $next): Response
    {
        Auth::forgetGuards();
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        try {
            $jwt = app('tymon.jwt')->setRequest($request)->setToken($token);

            if (! $jwt->check(true)) {
                return response()->json(['message' => 'No autenticado.'], 401);
            }

            $guard = Auth::guard('api')->setRequest($request)->setToken($token);
            $user = $guard->user();

            if (! $user) {
                return response()->json(['message' => 'No autenticado.'], 401);
            }

            Auth::shouldUse('api');

            return $next($request);
        } catch (JWTException) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }
    }
}
