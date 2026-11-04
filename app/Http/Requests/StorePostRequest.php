<?php

namespace App\Http\Requests;

use App\Models\Post;
use App\Rules\NoUrls;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Post::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $images = config('media.images');
        $videos = config('media.videos');

        return [
            'title' => ['required', 'string', 'max:150', new NoUrls('Links aren\'t allowed in the title. Describe your post in words instead.')],
            'body' => ['nullable', 'string', 'max:3000'],
            'photos' => ['nullable', 'array', 'max:'.$images['max_files']],
            'photos.*' => [
                'required',
                'file',
                'image',
                'extensions:'.implode(',', $images['extensions']),
                'mimes:'.implode(',', $images['extensions']),
                'mimetypes:'.implode(',', $images['mime_types']),
                'max:'.$images['max_kb'],
            ],
            'videos' => ['nullable', 'array', 'max:'.$videos['max_files']],
            'videos.*' => [
                'required',
                'file',
                'extensions:'.implode(',', $videos['extensions']),
                'mimetypes:'.implode(',', $videos['mime_types']),
                'max:'.$videos['max_kb'],
            ],
            'agree' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $images = config('media.images');
        $videos = config('media.videos');

        return [
            'agree.accepted' => 'Please confirm you have read the community rules and own or have permission to share this media.',
            'photos.max' => "You can attach up to {$images['max_files']} photos.",
            'photos.*.uploaded' => 'A photo could not be uploaded. It may be larger than the server allows.',
            'photos.*.image' => 'Each photo must be a valid '.$images['label'].' image.',
            'photos.*.extensions' => 'Photos must be '.$images['label'].' files.',
            'photos.*.mimes' => 'Photos must be '.$images['label'].' files.',
            'photos.*.mimetypes' => 'Photos must be '.$images['label'].' files.',
            'photos.*.max' => 'Each photo must be '.self::formatKb($images['max_kb']).' or smaller.',
            'videos.max' => "You can attach up to {$videos['max_files']} videos.",
            'videos.*.uploaded' => 'A video could not be uploaded. It may be larger than the server allows.',
            'videos.*.extensions' => 'Videos must be '.$videos['label'].' files.',
            'videos.*.mimetypes' => 'Videos must be '.$videos['label'].' files.',
            'videos.*.max' => 'Each video must be '.self::formatKb($videos['max_kb']).' or smaller.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'photos.*' => 'photo',
            'videos.*' => 'video',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('title'))) {
            $this->merge(['title' => trim($this->input('title'))]);
        }
    }

    public static function formatKb(int $kilobytes): string
    {
        return $kilobytes >= 1024 ? round($kilobytes / 1024, 1).' MB' : $kilobytes.' KB';
    }
}
