<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }

    public function handle($request, Closure $next, ...$guards)
    {
        $this->setBearerToken($request);

        $this->authenticate($request, $guards);

        return $next($request);
    }

    private function isBase64($s)
    {
          return (bool) preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $s);
    }

    private function setBearerToken($request): void
    {
        $token = null;
        if ($request->expectsJson() && $request->hasCookie('promigo_token')) {
            $token = $request->cookie('promigo_token');
        }

        if ($request->has('promigo_token')) {
            $token = $request->input('promigo_token');
            $request->headers->set('Authorization', 'Bearer ' . $request->input('promigo_token'));
        }

        try {

            if ($this->isBase64($token)) {
                $token = Crypt::decryptString($token);
                [$timestamp, $userId, $token] = explode('|', $token, 3);
            }

            $request->headers->set('Authorization', "Bearer $token");


        } catch (\Exception $exception) {
            // do nothing
        }
    }
}
