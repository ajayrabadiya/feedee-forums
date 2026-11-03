<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SupportAnalyticsRequest;
use App\Models\SupportClick;
use Illuminate\Http\JsonResponse;

class SupportAnalyticsController extends Controller
{
    /**
     * Support button click stats for the dashboard's filters and live refresh.
     */
    public function __invoke(SupportAnalyticsRequest $request): JsonResponse
    {
        [$from, $to] = $request->customDates();

        return response()->json(SupportClick::analytics($request->range(), $request->group(), $from, $to));
    }
}
