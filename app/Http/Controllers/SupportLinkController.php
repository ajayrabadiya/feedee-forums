<?php

namespace App\Http\Controllers;

use App\Enums\SupportPlatform;
use App\Http\Requests\StoreSupportClickRequest;
use App\Models\Setting;
use App\Models\SupportClick;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class SupportLinkController extends Controller
{
    /**
     * Record a click on a Support button payment link, then send the visitor to the link configured
     * in Admin > Settings. The destination never comes from the request, so this cannot be used as
     * an open redirect, and a failure to record the click never stops the redirect.
     */
    public function __invoke(Request $request, SupportPlatform $platform): RedirectResponse
    {
        $url = Setting::supportButton()['enabled'] ? $platform->url() : null;

        abort_if($url === null, 404);

        if ($this->shouldRecord($request)) {
            try {
                SupportClick::record($request, $platform, $this->referrerPath($request), $url);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return redirect()->away($url)->withHeaders([
            'Cache-Control' => 'no-store, private',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    /**
     * Link previews, prefetches, admins and visitors over the rate limit are redirected without being recorded.
     */
    private function shouldRecord(Request $request): bool
    {
        $isPrefetch = str_contains(strtolower($request->header('Sec-Purpose', '').$request->header('Purpose', '').$request->header('X-Moz', '')), 'prefetch');

        if ($request->isMethod('HEAD') || $isPrefetch || $request->user()?->isAdmin()) {
            return false;
        }

        return RateLimiter::attempt(
            'support-link:'.sha1((string) $request->ip()),
            (int) config('analytics.support_clicks.rate_limit'),
            fn (): bool => true,
        ) !== false;
    }

    /**
     * Path of the page on this site the visitor clicked from: the "from" path the Support button adds to
     * its links, or else the Referer header. Query strings, other sites and unsafe paths are dropped.
     */
    private function referrerPath(Request $request): ?string
    {
        $path = $request->query('from');

        if (! is_string($path) || $path === '') {
            $referrer = (string) $request->headers->get('referer');

            if ($referrer === '' || parse_url($referrer, PHP_URL_HOST) !== $request->getHost()) {
                return null;
            }

            $path = (string) parse_url($referrer, PHP_URL_PATH) ?: '/';
        }

        return mb_strlen($path) <= 255 && preg_match(StoreSupportClickRequest::PAGE_PATH_PATTERN, $path) === 1 ? $path : null;
    }
}
