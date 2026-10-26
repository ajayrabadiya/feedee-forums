<?php

namespace App\Http\Requests;

use App\Models\ContactMessage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreContactMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'subject' => ['required', 'string', Rule::in(ContactMessage::SUBJECTS)],
            'post_id' => ['nullable', 'string', 'max:12', 'regex:/^[A-Za-z0-9]+$/'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subject.in' => 'Please choose one of the listed subjects.',
            'post_id.regex' => 'Post IDs only contain letters and numbers, e.g. k7Xq92Lm.',
            'message.min' => 'Please tell us a little more (at least 10 characters).',
            'website.prohibited' => 'Your message could not be sent.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'email' => is_string($this->input('email')) ? Str::lower(trim($this->input('email'))) : $this->input('email'),
            'post_id' => is_string($this->input('post_id')) ? ltrim(trim($this->input('post_id')), '#') : $this->input('post_id'),
        ]);
    }
}
