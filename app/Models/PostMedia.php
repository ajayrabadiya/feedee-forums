<?php

namespace App\Models;

use App\Enums\MediaType;
use Database\Factories\PostMediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['media_type', 'disk', 'file_path', 'original_name', 'alt_text', 'mime_type', 'file_size'])]
class PostMedia extends Model
{
    /** @use HasFactory<PostMediaFactory> */
    use HasFactory;

    protected $table = 'post_media';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'media_type' => MediaType::class,
            'file_size' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function isImage(): bool
    {
        return $this->media_type === MediaType::Image;
    }

    public function isVideo(): bool
    {
        return $this->media_type === MediaType::Video;
    }

    /**
     * Public URL for the stored file.
     */
    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->file_path);
    }

    /**
     * Delete the stored file, refusing any path outside the media upload directories.
     */
    public function deleteFile(): bool
    {
        if (! self::isSafeMediaPath($this->file_path)) {
            return false;
        }

        return Storage::disk($this->disk)->delete($this->file_path);
    }

    /**
     * Only plain relative paths inside the configured image/video directories are allowed.
     */
    public static function isSafeMediaPath(string $path): bool
    {
        if ($path === '' || str_contains($path, '..') || str_contains($path, '\\') || str_starts_with($path, '/')) {
            return false;
        }

        foreach (MediaType::cases() as $type) {
            if (Str::startsWith($path, rtrim($type->directory(), '/').'/')) {
                return true;
            }
        }

        return false;
    }
}
