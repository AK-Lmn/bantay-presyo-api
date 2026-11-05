<?php

namespace App\Http\Middleware;

use App\Http\ApiExceptionRenderer;
use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            return ApiExceptionRenderer::error(403, 'email_not_verified', 'Verify your email address first.');
        }

        return $next($request);
    }
}
