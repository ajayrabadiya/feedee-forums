<?php

namespace App\Http\Requests\Admin;

use App\Enums\SupportClickRange;
use App\Models\SupportClick;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SupportAnalyticsRequest extends FormRequest
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
            'range' => ['sometimes', Rule::enum(SupportClickRange::class)],
            'group' => ['sometimes', Rule::in(SupportClick::GROUPS)],
            'from' => ['exclude_unless:range,custom', 'required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'to' => ['exclude_unless:range,custom', 'required', 'date_format:Y-m-d', 'after_or_equal:from', 'before_or_equal:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'from.required' => 'Choose a start date for the custom range.',
            'to.required' => 'Choose an end date for the custom range.',
            'from.before_or_equal' => 'The start date cannot be in the future.',
            'to.before_or_equal' => 'The end date cannot be in the future.',
            'to.after_or_equal' => 'The end date must be on or after the start date.',
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty() || $this->range() !== SupportClickRange::Custom) {
                    return;
                }

                [$from, $to] = $this->customDates();

                if ($from->diffInDays($to) + 1 > SupportClick::MAX_RANGE_DAYS) {
                    $validator->errors()->add('to', 'Custom ranges can cover up to '.SupportClick::MAX_RANGE_DAYS.' days.');
                }
            },
        ];
    }

    public function range(): SupportClickRange
    {
        return SupportClickRange::tryFrom((string) $this->input('range')) ?? SupportClickRange::Last7Days;
    }

    public function group(): string
    {
        return in_array($this->input('group'), SupportClick::GROUPS, true) ? $this->input('group') : 'day';
    }

    /**
     * Start and end dates of a custom range, or nulls for the preset ranges.
     *
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    public function customDates(): array
    {
        if ($this->range() !== SupportClickRange::Custom) {
            return [null, null];
        }

        return [
            Carbon::createFromFormat('Y-m-d', $this->input('from'))->startOfDay(),
            Carbon::createFromFormat('Y-m-d', $this->input('to'))->startOfDay(),
        ];
    }
}
