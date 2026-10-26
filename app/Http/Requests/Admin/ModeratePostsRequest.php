<?php

namespace App\Http\Requests\Admin;

use App\Models\Post;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModeratePostsRequest extends FormRequest
{
    /**
     * Supported moderation actions.
     *
     * @var list<string>
     */
    public const ACTIONS = ['approve', 'reject', 'feature', 'unfeature', 'delete'];

    /**
     * Maximum number of posts handled in one request.
     */
    private const MAX_POSTS = 100;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('moderate', Post::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'string', Rule::in(self::ACTIONS)],
            'ids' => ['required', 'array', 'min:1', 'max:'.self::MAX_POSTS],
            'ids.*' => ['required', 'integer', 'distinct', Rule::exists(Post::class, 'id')],
            'reason' => ['exclude_unless:action,reject', 'required', 'string', Rule::in(Post::REJECTION_REASONS)],
            'note' => ['exclude_unless:action,reject', 'nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'Select at least one post.',
            'ids.*.exists' => 'One of the selected posts no longer exists. Refresh the page and try again.',
            'reason.required' => 'Choose a reason for rejecting the post.',
            'reason.in' => 'Choose one of the listed rejection reasons.',
        ];
    }
}
