<?php

namespace App\Enums;

enum MediaType: string
{
    case Image = 'image';
    case Video = 'video';

    /**
     * Key of this media type's settings in config/media.php.
     */
    public function configKey(): string
    {
        return match ($this) {
            self::Image => 'images',
            self::Video => 'videos',
        };
    }

    /**
     * Storage directory (relative to the media disk) for this media type.
     */
    public function directory(): string
    {
        return config("media.{$this->configKey()}.directory");
    }
}
