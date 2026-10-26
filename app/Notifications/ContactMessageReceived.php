<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactMessageReceived extends Notification
{
    use Queueable;

    public function __construct(public ContactMessage $contactMessage) {}

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
        $contact = $this->contactMessage;

        $mail = (new MailMessage)
            ->subject("New contact message: {$contact->subject}")
            ->replyTo($contact->email, $contact->name)
            ->greeting('New message from the contact form')
            ->line("From: {$contact->name} <{$contact->email}>")
            ->line("Subject: {$contact->subject}");

        if ($contact->post_reference) {
            $mail->line("Post ID: {$contact->post_reference}");
        }

        return $mail
            ->line('Message:')
            ->line($contact->message)
            ->action('Open in admin panel', route('admin.contacts.show', $contact))
            ->line('Reply to this email to answer the sender directly.');
    }
}
