<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMailSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mail_mailer' => ['required', Rule::in(['log', 'smtp'])],
            'mail_host' => ['exclude_unless:mail_mailer,smtp', 'required', 'string', 'max:255', 'regex:/^[A-Za-z0-9.-]+$/'],
            'mail_port' => ['exclude_unless:mail_mailer,smtp', 'required', 'integer', 'between:1,65535'],
            'mail_encryption' => ['exclude_unless:mail_mailer,smtp', 'required', Rule::in(['tls', 'ssl', 'none'])],
            'mail_username' => ['exclude_unless:mail_mailer,smtp', 'nullable', 'string', 'max:255'],
            'mail_password' => ['exclude_unless:mail_mailer,smtp', 'nullable', 'string', 'max:255'],
            'mail_from_address' => ['required', 'email', 'max:255'],
            'mail_from_name' => ['required', 'string', 'max:100'],
            'mail_contact_recipient' => ['nullable', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mail_host.required' => 'Enter your SMTP server, e.g. smtp.gmail.com.',
            'mail_host.regex' => 'Enter just the server name, e.g. smtp.gmail.com (no http:// or spaces).',
            'mail_port.required' => 'Enter the SMTP port, usually 587 (TLS) or 465 (SSL).',
        ];
    }
}
