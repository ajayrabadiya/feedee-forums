<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSupportSettingsRequest extends FormRequest
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
            'support_enabled' => ['nullable', 'boolean'],
            'youpay_url' => ['nullable', 'url:https', 'max:255'],
            'youpay_note' => ['nullable', 'string', 'max:60'],
            'cashapp_url' => ['nullable', 'url:https', 'max:255'],
            'cashapp_note' => ['nullable', 'string', 'max:60'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'youpay_url.url' => 'Enter a full https:// link, e.g. https://youpay.me/yourname.',
            'cashapp_url.url' => 'Enter a full https:// link, e.g. https://cash.app/$yourname.',
        ];
    }
}
