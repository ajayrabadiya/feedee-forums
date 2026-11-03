<?php

namespace App\Http\Requests\Admin;

use App\Enums\SupportClickRange;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Filters for the Support link analytics page, its activity log and CSV exports.
 */
class SupportLinkAnalyticsRequest extends SupportAnalyticsRequest
{
    /**
     * CSV reports the page can export.
     *
     * @var list<string>
     */
    public const REPORTS = ['countries', 'daily', 'clicks'];

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'page' => ['sometimes', 'integer', 'min:1', 'max:100000'],
            'report' => ['sometimes', Rule::in(self::REPORTS)],
        ];
    }

    public function report(): string
    {
        return in_array($this->input('report'), self::REPORTS, true) ? $this->input('report') : 'countries';
    }

    protected function defaultRange(): SupportClickRange
    {
        return SupportClickRange::Last30Days;
    }

    /**
     * @return list<string>
     */
    protected function groups(): array
    {
        return ['day', 'week', 'month'];
    }
}
