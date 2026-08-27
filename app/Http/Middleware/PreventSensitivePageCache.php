<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventSensitivePageCache
{
    /**
     * Halaman login & area terautentikasi tidak boleh disimpan cache browser (bfcache / tombol Back).
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldPreventCache($request)) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }

    private function shouldPreventCache(Request $request): bool
    {
        if ($request->user() !== null) {
            return true;
        }

        return $request->routeIs('login');
    }
}
