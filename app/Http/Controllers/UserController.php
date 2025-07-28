<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * Get the authenticated user's role.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRole(Request $request): JsonResponse
    {
        return response()->json([
            'role' => $request->user()->getRoleNames()->first()
        ]);
    }
}
