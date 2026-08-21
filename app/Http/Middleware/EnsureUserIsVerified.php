<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsVerified {
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response {
        if (!$request->user() || !$request->user()->is_verified) {
            return response()->json([
                'message' => 'Please verify your phone number first.',
            ], 403);
        }
        return $next($request);
    }
}
