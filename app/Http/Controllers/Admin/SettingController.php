<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateMailSettingsRequest;
use App\Http\Requests\Admin\UpdateSupportSettingsRequest;
use App\Mail\TestEmail;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings', [
            'support' => [
                'enabled' => Setting::get('support.enabled') === '1',
                'youpay_url' => Setting::get('support.youpay_url'),
                'youpay_note' => Setting::get('support.youpay_note'),
                'cashapp_url' => Setting::get('support.cashapp_url'),
                'cashapp_note' => Setting::get('support.cashapp_note'),
            ],
            'mail' => [
                'mailer' => Setting::get('mail.mailer', config('mail.default')),
                'host' => Setting::get('mail.host', config('mail.mailers.smtp.host')),
                'port' => Setting::get('mail.port', (string) config('mail.mailers.smtp.port')),
                'encryption' => Setting::get('mail.encryption', 'tls'),
                'username' => Setting::get('mail.username', config('mail.mailers.smtp.username')),
                'has_password' => filled(Setting::get('mail.password')),
                'from_address' => Setting::get('mail.from_address', config('mail.from.address')),
                'from_name' => Setting::get('mail.from_name', config('mail.from.name')),
                'contact_recipient' => Setting::get('mail.contact_recipient'),
                'saved' => Setting::get('mail.mailer') !== null,
            ],
            'testRecipient' => request()->user()->email,
        ]);
    }

    public function updateSupport(UpdateSupportSettingsRequest $request): RedirectResponse
    {
        Setting::setMany([
            'support.enabled' => $request->boolean('support_enabled') ? '1' : '0',
            'support.youpay_url' => $request->validated('youpay_url') ?? '',
            'support.youpay_note' => $request->validated('youpay_note') ?? '',
            'support.cashapp_url' => $request->validated('cashapp_url') ?? '',
            'support.cashapp_note' => $request->validated('cashapp_note') ?? '',
        ]);

        return redirect()->route('admin.settings')->with('status', 'Support button settings saved.');
    }

    public function updateMail(UpdateMailSettingsRequest $request): RedirectResponse
    {
        $values = [
            'mail.mailer' => $request->validated('mail_mailer'),
            'mail.from_address' => $request->validated('mail_from_address'),
            'mail.from_name' => $request->validated('mail_from_name'),
            'mail.contact_recipient' => $request->validated('mail_contact_recipient') ?? '',
        ];

        if ($request->validated('mail_mailer') === 'smtp') {
            $values += [
                'mail.host' => $request->validated('mail_host'),
                'mail.port' => (string) $request->validated('mail_port'),
                'mail.encryption' => $request->validated('mail_encryption'),
                'mail.username' => $request->validated('mail_username') ?? '',
            ];

            if (filled($request->validated('mail_password'))) {
                $values['mail.password'] = $request->validated('mail_password');
            }
        }

        Setting::setMany($values);

        return redirect()->route('admin.settings')->with('status', 'Email settings saved. Send a test email to check them.');
    }

    public function sendTestMail(Request $request): RedirectResponse
    {
        $recipient = $request->validate(['test_recipient' => ['required', 'email', 'max:255']])['test_recipient'];

        Setting::applyMailConfig();

        try {
            Mail::to($recipient)->send(new TestEmail);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('admin.settings')
                ->withInput(['test_recipient' => $recipient])
                ->withErrors(['test_recipient' => 'The test email could not be sent: '.Str::limit($exception->getMessage(), 300)], 'mailTest');
        }

        $where = config('mail.default') === 'log'
            ? ' The mailer is set to "Log only", so it was written to storage/logs/laravel.log instead of being delivered.'
            : '';

        return redirect()->route('admin.settings')->with('status', "Test email sent to {$recipient}.{$where}");
    }

    /**
     * Turn "Require approval for new posts" on or off.
     */
    public function updatePostApproval(Request $request): JsonResponse
    {
        $enabled = $request->validate(['enabled' => ['required', 'boolean']])['enabled'];
        $enabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);

        Setting::set(Setting::REQUIRE_POST_APPROVAL, $enabled ? '1' : '0');

        return response()->json([
            'enabled' => $enabled,
            'message' => $enabled
                ? 'Approval turned on. New posts will wait for review.'
                : 'Approval turned off. New posts go live instantly.',
        ]);
    }
}
