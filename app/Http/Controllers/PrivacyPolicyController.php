<?php

namespace App\Http\Controllers;

use App\Models\PrivacyPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Stevebauman\Purify\Facades\Purify;

class PrivacyPolicyController extends Controller
{
    /**
     * Get the current privacy policy
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show()
    {
        // Use caching to improve performance
        $privacyPolicy = Cache::remember('privacy_policy', 3600, function () {
            return PrivacyPolicy::first();
        });

        if (!$privacyPolicy) {
            return response()->json([
                'success' => false,
                'message' => 'Privacy policy not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $privacyPolicy
        ]);
    }

    /**
     * Update the privacy policy content
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:100000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update privacy policy',
                'errors' => $validator->errors()
            ], 400);
        }

        // Sanitize the HTML content to prevent XSS attacks
        $sanitizedContent = Purify::clean($request->content);

        // Get the first privacy policy or create a new one if none exists
        $privacyPolicy = PrivacyPolicy::first();
        if (!$privacyPolicy) {
            $privacyPolicy = new PrivacyPolicy();
        }

        // Update the content
        $privacyPolicy->content = $sanitizedContent;
        $privacyPolicy->save();

        // Clear the cache
        Cache::forget('privacy_policy');

        return response()->json([
            'success' => true,
            'data' => $privacyPolicy,
            'message' => 'Privacy policy updated successfully'
        ]);
    }
}
