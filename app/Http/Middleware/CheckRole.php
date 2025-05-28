<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $role): Response {
        if (!$request->user()) {
            return response()->json(['message' => 'User tidak memiliki autorisasi'], 401);
        }

        if (!$request->user()->hasRole($role)) {
            return response()->json(['message' => 'Untuk mengakses halaman ini membutuhkan role ' . $role], 403);
        }

        return $next($request);
    }
}
