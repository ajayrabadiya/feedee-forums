<?php

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PostRejected extends Notification
{
    use Queueable;

    public function __construct(public Post $post) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Your Feedee Forums post was not approved')
            ->greeting("Hi {$notifiable->name},")
            ->line("A moderator reviewed your post \"{$this->post->title}\" and it wasn't approved.")
            ->line("Reason: {$this->post->rejection_reason}");

        if (filled($this->post->rejection_note)) {
            $message->line("Message from the moderator: {$this->post->rejection_note}");
        }

        return $message
            ->line('You can edit the post to fix the problem and resubmit it for review.')
            ->action('Edit your post', route('posts.edit', $this->post));
    }
}
