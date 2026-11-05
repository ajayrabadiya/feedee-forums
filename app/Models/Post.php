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
     * Number of days a post stays live after approval before it is permanently deleted.
     *
     * The lifetime starts at approved_at (when the post becomes Approved), not created_at.
     * Re-approving a previously rejected post starts a new 30-day window.
     * Editing an already-approved post does not change approved_at.
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
            'approved_at' => 'datetime',
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
     * @return HasMany<PostLike, $this>
     */
    public function likes(): HasMany
    {
        return $this->hasMany(PostLike::class);
    }

    /**
     * Add like counts, and whether the given user liked each post, to a query.
     *
     * @param  Builder<Post>  $query
     */
    public function scopeWithLikeInfo(Builder $query, ?User $user): void
    {
        $query->withCount('likes')->withExists([
            'likes as liked_by_viewer' => fn (Builder $likes) => $likes->where('user_id', $user?->id ?? 0),
        ]);
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
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
     * Approved posts still inside their approval-based 30-day lifetime.
     *
     * A post approved exactly LIFETIME_DAYS ago is no longer live (eligible for purge).
     *
     * @param  Builder<Post>  $query
     */
    public function scopeLive(Builder $query): void
    {
        $query->whereNotNull('approved_at')
            ->where('approved_at', '>', now()->subDays(self::LIFETIME_DAYS));
    }

    /**
     * Approved posts whose 30-day lifetime has ended (awaiting or eligible for purge).
     *
     * @param  Builder<Post>  $query
     */
    public function scopeExpired(Builder $query): void
    {
        $query->where('status', PostStatus::Approved)
            ->whereNotNull('approved_at')
            ->where('approved_at', '<=', now()->subDays(self::LIFETIME_DAYS));
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

    /**
     * Whether this approved post has passed its approval-based expiry.
     */
    public function isExpired(): bool
    {
        return $this->isApproved()
            && $this->approved_at !== null
            && $this->approved_at->lte(now()->subDays(self::LIFETIME_DAYS));
    }

    /**
     * Whether this post is approved and still within its public lifetime.
     */
    public function isWithinLifetime(): bool
    {
        return $this->isApproved()
            && $this->approved_at !== null
            && $this->approved_at->gt(now()->subDays(self::LIFETIME_DAYS));
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
        $alreadyApproved = $this->isApproved() && $this->approved_at !== null;

        $this->forceFill([
            'status' => PostStatus::Approved,
            'rejection_reason' => null,
            'rejection_note' => null,
            'moderated_by' => $moderator->id,
            'moderated_at' => now(),
            // Keep the original approval time when re-clicking approve on a live post.
            // Re-approving after rejection starts a new 30-day window.
            'approved_at' => $alreadyApproved ? $this->approved_at : now(),
        ])->save();
    }

    /**
     * Mark the post as approved at creation time (auto-publish / admin author).
     */
    public function markApprovedAtCreation(): void
    {
        if ($this->approved_at === null) {
            $this->approved_at = now();
        }
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
     * Date the post is scheduled to be permanently deleted (approval + 30 days).
     * Null until the post has been approved.
     */
    public function expiresAt(): ?Carbon
    {
        if ($this->approved_at === null) {
            return null;
        }

        return $this->approved_at->copy()->addDays(self::LIFETIME_DAYS);
    }

    /**
     * Allowed HTML tags for rich post bodies.
     */
    public const BODY_ALLOWED_TAGS = '<p><br><strong><b><em><i><u><ul><ol><li><blockquote>';

    /**
     * Maximum plain-text length for the optional post body.
     */
    public const BODY_MAX_LENGTH = 3000;

    public static function plainBody(?string $body): string
    {
        if ($body === null || trim($body) === '') {
            return '';
        }

        return trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    public static function sanitizeBody(?string $body): ?string
    {
        if ($body === null) {
            return null;
        }

        $clean = trim(strip_tags($body, self::BODY_ALLOWED_TAGS));
        $plain = self::plainBody($clean);

        return $plain === '' ? null : $clean;
    }

    /**
     * HTML safe to load into the create/edit rich text editor.
     */
    public static function bodyForEditor(?string $body): string
    {
        $clean = self::sanitizeBody($body);

        if ($clean === null) {
            return '';
        }

        if (preg_match('/<(?:p|br|strong|b|em|i|u|ul|ol|li|blockquote)\b/i', $clean) === 1) {
            return $clean;
        }

        $paragraphs = preg_split('/\R{2,}/', trim($clean)) ?: [];

        return collect($paragraphs)
            ->map(fn (string $paragraph): string => '<p>'.nl2br(e($paragraph)).'</p>')
            ->implode('');
    }

    /**
     * HTML safe to render on the post page.
     */
    public function bodyHtml(): string
    {
        return self::bodyForEditor($this->body);
    }

    /**
     * Plain-text excerpt for cards and meta descriptions.
     */
    public function bodyExcerpt(int $limit = 220): string
    {
        return Str::limit(self::plainBody($this->body), $limit);
    }
}
