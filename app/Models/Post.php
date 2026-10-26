<?php

namespace App\Models;

use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

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
     * Number of days a post stays live before it is permanently deleted.
     */
    public const LIFETIME_DAYS = 30;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Date the post is scheduled to be permanently deleted.
     */
    public function expiresAt(): Carbon
    {
        return $this->created_at->copy()->addDays(self::LIFETIME_DAYS);
    }
}
