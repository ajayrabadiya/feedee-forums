<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupportClickRequest;
use App\Models\SupportClick;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class SupportClickController extends Controller
{
    /**
     * Record a click on the floating Support button. Clicks by administrators are not counted.
     */
    public function __invoke(StoreSupportClickRequest $request): Response
    {
        $user = $request->user();

        if (! $user?->isAdmin()) {
            $click = new SupportClick($request->safe()->only(['page_path']));
            $click->user_id = $user?->id;
            $click->ip_address = $request->ip();
            $click->user_agent = Str::limit((string) $request->userAgent(), 255, '') ?: null;
            $click->save();
        }

        return response()->noContent();
    }
}
