<?php

namespace App\Models;

use App\Enums\MediaType;
use App\Enums\PostStatus;
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

    /**
     * Reasons an administrator can give when rejecting a post.
     *
     * @var list<string>
     */
    public const REJECTION_REASONS = [
        'Breaks community rules',
        'Spam or self-promotion',
        'Inappropriate media',
        'Off topic',
        'Duplicate post',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'approved',
        'is_featured' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'is_featured' => 'boolean',
            'featured_at' => 'datetime',
            'moderated_at' => 'datetime',
        ];
    }

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

    /**
     * Posts the public can see: approved and still inside their lifetime.
     *
     * @param  Builder<Post>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', PostStatus::Approved)->live();
    }

    public function isApproved(): bool
    {
        return $this->status === PostStatus::Approved;
    }

    public function isPending(): bool
    {
        return $this->status === PostStatus::Pending;
    }

    public function isRejected(): bool
    {
        return $this->status === PostStatus::Rejected;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function approve(User $moderator): void
    {
        $this->forceFill([
            'status' => PostStatus::Approved,
            'rejection_reason' => null,
            'rejection_note' => null,
            'moderated_by' => $moderator->id,
            'moderated_at' => now(),
        ])->save();
    }

    public function reject(User $moderator, string $reason, ?string $note = null): void
    {
        $this->forceFill([
            'status' => PostStatus::Rejected,
            'is_featured' => false,
            'featured_at' => null,
            'rejection_reason' => $reason,
            'rejection_note' => $note,
            'moderated_by' => $moderator->id,
            'moderated_at' => now(),
        ])->save();
    }

    /**
     * Featuring a post also approves it, so it can be shown at the top of the feed.
     */
    public function feature(User $moderator): void
    {
        if (! $this->isApproved()) {
            $this->approve($moderator);
        }

        $this->forceFill([
            'is_featured' => true,
            'featured_at' => now(),
            'moderated_by' => $moderator->id,
            'moderated_at' => now(),
        ])->save();
    }

    public function unfeature(): void
    {
        $this->forceFill(['is_featured' => false, 'featured_at' => null])->save();
    }

    /**
     * Send the post back to the review queue (used when an author edits a rejected post).
     */
    public function resubmit(): void
    {
        $this->forceFill([
            'status' => PostStatus::Pending,
            'rejection_reason' => null,
            'rejection_note' => null,
        ])->save();
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
