<?php

namespace App\Models;

use App\Enums\MediaType;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Fillable(['title', 'topic', 'body'])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    /**
     * Topics a post can be filed under.
     *
     * @var list<string>
     */
    public const TOPICS = [
        'Recipes',
        'The Lounge',
        'Meetups',
        'Art & Stories',
        'Outdoors',
        'Introductions',
    ];

    /**
     * Colour class and icon used for each topic in the feed.
     *
     * @var array<string, array{color: string, icon: string}>
     */
    public const TOPIC_STYLES = [
        'Recipes' => ['color' => 't-amber', 'icon' => 'bi-cup-hot'],
        'The Lounge' => ['color' => 't-purple', 'icon' => 'bi-chat-dots'],
        'Meetups' => ['color' => 't-blue', 'icon' => 'bi-calendar-event'],
        'Art & Stories' => ['color' => 't-pink', 'icon' => 'bi-palette'],
        'Outdoors' => ['color' => 't-green', 'icon' => 'bi-tree'],
        'Introductions' => ['color' => 't-orange', 'icon' => 'bi-emoji-smile'],
    ];

    /**
     * Number of days a post stays live before it is permanently deleted.
     */
    public const LIFETIME_DAYS = 30;

    protected static function booted(): void
    {
        static::creating(function (Post $post): void {
            $post->public_id ??= static::generatePublicId();
        });

        static::deleting(function (Post $post): void {
            $post->loadMissing('media');
        });

        static::deleted(function (Post $post): void {
            $media = $post->media;

            DB::afterCommit(fn () => $media->each->deleteFile());
        });
    }

    public static function generatePublicId(): string
    {
        do {
            $publicId = Str::random(8);
        } while (static::where('public_id', $publicId)->exists());

        return $publicId;
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<PostMedia, $this>
     */
    public function media(): HasMany
    {
        return $this->hasMany(PostMedia::class)->orderBy('id');
    }

    /**
     * @return HasMany<PostMedia, $this>
     */
    public function images(): HasMany
    {
        return $this->media()->where('media_type', MediaType::Image);
    }

    /**
     * @return HasMany<PostMedia, $this>
     */
    public function videos(): HasMany
    {
        return $this->media()->where('media_type', MediaType::Video);
    }

    /**
     * Only posts still inside their 30-day lifetime.
     *
     * @param  Builder<Post>  $query
     */
    public function scopeLive(Builder $query): void
    {
        $query->where('created_at', '>=', now()->subDays(self::LIFETIME_DAYS));
    }

    public function topicColor(): string
    {
        return self::TOPIC_STYLES[$this->topic]['color'] ?? 't-orange';
    }

    /**
     * Date the post is scheduled to be permanently deleted.
     */
    public function expiresAt(): Carbon
    {
        return $this->created_at->copy()->addDays(self::LIFETIME_DAYS);
    }
}
