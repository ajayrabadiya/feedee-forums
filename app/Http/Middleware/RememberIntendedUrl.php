<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RememberIntendedUrl
{
    /**
     * Paths that must never be used as a post-login destination.
     *
     * @var list<string>
     */
    private const EXCLUDED_PATHS = ['/login', '/register', '/logout'];

    /**
     * Store a same-site `redirect` query value as the intended URL so the
     * visitor returns to the page they were viewing after logging in.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $redirect = $request->query('redirect');

        if (is_string($redirect) && self::isSafeRelativePath($redirect)) {
            $request->session()->put('url.intended', url($redirect));
        }

        return $next($request);
    }

    /**
     * Accept only local, root-relative paths to prevent open redirects.
     */
    public static function isSafeRelativePath(string $path): bool
    {
        if ($path === '' || strlen($path) > 2048) {
            return false;
        }

        if (! str_starts_with($path, '/') || str_starts_with($path, '//') || str_contains($path, '\\')) {
            return false;
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $path) === 1) {
            return false;
        }

        $parts = parse_url($path);

        if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) {
            return false;
        }

        return ! in_array(rtrim($parts['path'] ?? '/', '/'), self::EXCLUDED_PATHS, true);
    }
}
