<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSupportClickRequest extends FormRequest
{
    /**
     * A same-site path such as "/p/abc123": no scheme, host ("//"), backslashes, whitespace or control characters.
     */
    public const PAGE_PATH_PATTERN = '#^/(?![/\\\\])[^\s\\\\\x00-\x1F\x7F]*$#';

    /**
     * Anyone who can see the Support button may report a click on it.
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
            'page_path' => ['nullable', 'string', 'max:255', 'regex:'.self::PAGE_PATH_PATTERN],
        ];
    }
}
