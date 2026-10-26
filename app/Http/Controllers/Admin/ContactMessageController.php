<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactMessageController extends Controller
{
    public function index(): View
    {
        $messages = ContactMessage::query()->with('user')->latest()->latest('id')->get();

        return view('admin.contacts.index', [
            'messages' => $messages,
            'subjects' => ContactMessage::SUBJECTS,
            'unreadCount' => $messages->whereNull('read_at')->count(),
        ]);
    }

    /**
     * Show one message and mark it as read.
     */
    public function show(ContactMessage $contactMessage): View
    {
        $contactMessage->markAsRead();
        $contactMessage->load('user');

        return view('admin.contacts.show', [
            'contactMessage' => $contactMessage,
            'referencedPost' => $contactMessage->referencedPost(),
        ]);
    }

    /**
     * Mark a message as read or unread.
     */
    public function update(Request $request, ContactMessage $contactMessage): RedirectResponse
    {
        $read = $request->validate(['read' => ['required', 'boolean']])['read'];

        if (filter_var($read, FILTER_VALIDATE_BOOLEAN)) {
            $contactMessage->markAsRead();

            return back()->with('status', 'Message marked as read.');
        }

        $contactMessage->markAsUnread();

        return redirect()->route('admin.contacts.index')->with('status', 'Message marked as unread.');
    }

    public function destroy(ContactMessage $contactMessage): RedirectResponse
    {
        $contactMessage->delete();

        return redirect()->route('admin.contacts.index')->with('status', 'Message deleted.');
    }
}
