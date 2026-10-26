<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Post Media Disk
    |--------------------------------------------------------------------------
    |
    | Filesystem disk used to store photos and videos attached to posts. The
    | "public" disk serves files through the public/storage symlink (run
    | `php artisan storage:link`). Point this at another disk, such as
    | "s3", to move media to private or cloud storage later on.
    |
    */

    'disk' => env('MEDIA_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Upload Limits
    |--------------------------------------------------------------------------
    |
    | Sizes are in kilobytes. PHP's own limits (upload_max_filesize,
    | post_max_size and max_file_uploads in php.ini) must be at least as
    | large as these values, or uploads are rejected before Laravel runs.
    |
    */

    'images' => [
        'directory' => 'posts/images',
        'max_files' => (int) env('MEDIA_MAX_IMAGES', 10),
        'max_kb' => (int) env('MEDIA_IMAGE_MAX_KB', 5 * 1024),
        'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
        'mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
        'label' => 'JPG, PNG or WebP',
    ],

    'videos' => [
        'directory' => 'posts/videos',
        'max_files' => (int) env('MEDIA_MAX_VIDEOS', 2),
        'max_kb' => (int) env('MEDIA_VIDEO_MAX_KB', 50 * 1024),
        'max_seconds' => (int) env('MEDIA_VIDEO_MAX_SECONDS', 60),
        'extensions' => ['mp4', 'webm'],
        'mime_types' => ['video/mp4', 'video/webm'],
        'label' => 'MP4 or WebM',
    ],

];
