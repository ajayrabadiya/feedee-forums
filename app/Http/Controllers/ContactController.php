<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\ContactMessageReceived;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use Throwable;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('pages.contact', [
            'subjects' => ContactMessage::SUBJECTS,
        ]);
    }

    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        $contactMessage = new ContactMessage([
            ...$request->safe()->only(['name', 'email', 'subject', 'message']),
            'post_reference' => $request->validated('post_id') ?: null,
        ]);
        $contactMessage->user_id = $request->user()?->id;
        $contactMessage->ip_address = $request->ip();
        $contactMessage->save();

        try {
            Notification::send(
                User::query()->where('role', UserRole::Admin)->get(),
                new ContactMessageReceived($contactMessage),
            );

            $extraRecipient = Setting::get('mail.contact_recipient');

            if (filled($extraRecipient)) {
                Notification::route('mail', $extraRecipient)->notify(new ContactMessageReceived($contactMessage));
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()
            ->route('contact')
            ->with('status', "Thanks! Your message has been sent. We'll reply to {$contactMessage->email} soon.");
    }
}
