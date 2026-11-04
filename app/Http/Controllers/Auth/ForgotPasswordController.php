<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Throwable;

class ForgotPasswordController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Email a password reset link. The reply is the same whether or not the address has an account,
     * so the form cannot be used to find out who is registered.
     */
    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        $email = $request->validated('email');

        try {
            Password::sendResetLink(['email' => $email]);
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput(['email' => $email])
                ->withErrors(['email' => 'We couldn\'t send the email right now. Please try again in a few minutes.']);
        }

        return back()->with('resetLinkSent', $email);
    }
}
