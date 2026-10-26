<?php

namespace App\Http\Requests;

use App\Enums\MediaType;
use App\Models\Post;
use App\Models\PostMedia;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePostRequest extends StorePostRequest
{
    /**
     * Only the post's author or an administrator may update it.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->editedPost()) ?? false;
    }

    /**
     * Same field and upload rules as creating a post, plus removal of existing media.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'remove_media' => ['nullable', 'array'],
            'remove_media.*' => [
                'integer',
                'distinct',
                Rule::exists(PostMedia::class, 'id')->where('post_id', $this->editedPost()->id),
            ],
            'agree' => $this->hasNewMedia() ? ['accepted'] : ['nullable'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'remove_media.*.exists' => 'One of the selected media items does not belong to this post.',
            'remove_media.*.integer' => 'One of the selected media items is invalid.',
            'agree.accepted' => 'Please confirm you own or have permission to share the new photos and videos.',
        ];
    }

    /**
     * Enforce the per-post limits across kept and newly added media.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $kept = $this->editedPost()->media()->whereNotIn('id', $this->removedMediaIds())->get();

                $limits = [
                    'photos' => [MediaType::Image, config('media.images.max_files'), 'photos'],
                    'videos' => [MediaType::Video, config('media.videos.max_files'), 'videos'],
                ];

                foreach ($limits as $field => [$type, $max, $label]) {
                    $total = $kept->filter(fn (PostMedia $media): bool => $media->media_type === $type)->count() + count($this->file($field, []));

                    if ($total > $max && ! $validator->errors()->has($field)) {
                        $validator->errors()->add($field, "A post can have up to {$max} {$label}. Remove some existing {$label} or attach fewer new ones.");
                    }
                }
            },
        ];
    }

    public function editedPost(): Post
    {
        return $this->route('post');
    }

    /**
     * IDs of existing media the user chose to remove, limited to integers.
     *
     * @return list<int>
     */
    public function removedMediaIds(): array
    {
        $ids = $this->input('remove_media', []);

        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', array_filter($ids, 'is_numeric'))));
    }

    private function hasNewMedia(): bool
    {
        return $this->hasFile('photos') || $this->hasFile('videos');
    }
}
