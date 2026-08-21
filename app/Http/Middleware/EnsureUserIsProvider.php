<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsProvider {
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response {
        if (!$request->user() || !$request->user()->hasRole('provider')) {
            return response()->json([
                'message' => 'Only providers can access this resource.',
            ], 403);
        }

        return $next($request);
    }
}
