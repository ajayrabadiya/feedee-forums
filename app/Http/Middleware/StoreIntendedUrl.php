<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StoreIntendedUrl
{
    /**
     * Paths that must never be used as a post-login destination.
     *
     * @var list<string>
     */
    private const EXCLUDED_PATHS = ['login', 'register', 'logout'];

    /**
     * Remember the page the visitor came from (the "redirect" query parameter)
     * so they can be sent back to it after logging in or registering.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET')) {
            $target = $this->safeTarget($request->query('redirect'));

            if ($target !== null) {
                redirect()->setIntendedUrl($target);
            }
        }

        return $next($request);
    }

    /**
     * Accept only relative, same-site paths to prevent open redirects.
     */
    private function safeTarget(mixed $target): ?string
    {
        if (! is_string($target) || $target === '' || strlen($target) > 2000) {
            return null;
        }

        if (! str_starts_with($target, '/') || str_starts_with($target, '//') || str_contains($target, '\\')) {
            return null;
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $target) === 1) {
            return null;
        }

        $parts = parse_url($target);

        if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) {
            return null;
        }

        if (in_array(trim($parts['path'] ?? '', '/'), self::EXCLUDED_PATHS, true)) {
            return null;
        }

        return url($target);
    }
}
