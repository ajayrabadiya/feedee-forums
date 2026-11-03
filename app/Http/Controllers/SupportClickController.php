<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupportClickRequest;
use App\Models\SupportClick;
use Illuminate\Http\Response;

class SupportClickController extends Controller
{
    /**
     * Record a click on the floating Support button. Clicks by administrators are not counted.
     */
    public function __invoke(StoreSupportClickRequest $request): Response
    {
        if (! $request->user()?->isAdmin()) {
            SupportClick::record($request, pagePath: $request->validated('page_path'));
        }

        return response()->noContent();
    }
}
