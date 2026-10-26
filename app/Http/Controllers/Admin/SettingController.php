<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Turn "Require approval for new posts" on or off.
     */
    public function updatePostApproval(Request $request): JsonResponse
    {
        $enabled = $request->validate(['enabled' => ['required', 'boolean']])['enabled'];
        $enabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);

        Setting::set(Setting::REQUIRE_POST_APPROVAL, $enabled ? '1' : '0');

        return response()->json([
            'enabled' => $enabled,
            'message' => $enabled
                ? 'Approval turned on. New posts will wait for review.'
                : 'Approval turned off. New posts go live instantly.',
        ]);
    }
}
