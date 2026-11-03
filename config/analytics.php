<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Support Click Tracking
    |--------------------------------------------------------------------------
    |
    | Clicks on the floating Support button and its payment links. A click
    | only shows interest; it never means a payment was made.
    |
    | retention_days:   clicks older than this are deleted by the daily
    |                   `model:prune` run. Set to 0 to keep them forever.
    | duplicate_window: a second click on the same link by the same visitor
    |                   within this many seconds is kept but marked as a
    |                   duplicate, so double-clicks are not counted twice.
    | rate_limit:       link clicks recorded per visitor per minute. Extra
    |                   clicks still redirect, they are just not recorded.
    |
    */

    'support_clicks' => [
        'retention_days' => (int) env('SUPPORT_CLICKS_RETENTION_DAYS', 395),
        'duplicate_window' => (int) env('SUPPORT_CLICKS_DUPLICATE_WINDOW', 10),
        'rate_limit' => (int) env('SUPPORT_CLICKS_RATE_LIMIT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bot Detection
    |--------------------------------------------------------------------------
    |
    | Clicks whose user agent matches this pattern (or that send no user agent)
    | are stored as "bot" and left out of the counts.
    |
    */

    'bot_user_agents' => '/bot\b|bot\/|crawl|spider|slurp|scrape|preview|facebookexternalhit|embedly|headless|phantomjs|lighthouse|python-|curl\/|wget|httpclient|okhttp|go-http-client|axios\/|node-fetch|java\/|libwww|monitor|uptime/i',

    /*
    |--------------------------------------------------------------------------
    | Country Detection (IP Geolocation)
    |--------------------------------------------------------------------------
    |
    | header:        name of a trusted request header that already holds the
    |                visitor's ISO country code, e.g. "CF-IPCountry" when the
    |                site runs behind Cloudflare. Leave empty otherwise: the
    |                header could be faked by visitors.
    | download_url:  the free DB-IP "IP to Country Lite" database (CC BY 4.0)
    |                imported by `php artisan app:import-geoip`. "%s" is the
    |                year and month, e.g. 2026-11.
    | cache_seconds: how long a looked-up country is cached per IP address.
    |
    */

    'geoip' => [
        'header' => env('GEOIP_COUNTRY_HEADER'),
        'download_url' => env('GEOIP_DOWNLOAD_URL', 'https://download.db-ip.com/free/dbip-country-lite-%s.csv.gz'),
        'cache_seconds' => (int) env('GEOIP_CACHE_SECONDS', 86400),
    ],

];
