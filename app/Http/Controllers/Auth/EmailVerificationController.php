<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomEmailVerificationRequest;
use App\Mail\EmailVerificationMail;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmailVerificationController extends Controller
{
    /**
     * Mark the user's email address as verified.
     */
    public function __invoke(CustomEmailVerificationRequest $request): RedirectResponse
    {

        $user = $request->user();
        $user->email_verified_at = now();
        $user->save();

        if (!$user) {
            return redirect()->intended(
                config('app.frontend_url').'/dashboard?error=user-not-found'
            );
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(
                config('app.frontend_url').'/dashboard?verified=1&already_verified=1'
            );
        }

        // Use the fulfill method from our custom request class
        if ($request->fulfill()) {
            // No need to manually fire the Verified event as it's done in the fulfill method
            return redirect()->intended(
                config('app.frontend_url').'/dashboard?verified=1'
            );
        }

        return redirect()->intended(
            config('app.frontend_url').'/dashboard?error=verification-failed'
        );
    }

    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'User not authenticated.',
                'error' => 'unauthenticated'
            ], 401);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.',
                'email_verified' => true,
                'email_verified_at' => $user->email_verified_at
            ]);
        }

        // Send the verification email
        Mail::to($user->email)->send(new EmailVerificationMail($user));

        return response()->json([
            'message' => 'Verification link sent!',
            'email_verified' => false
        ]);
    }

    /**
     * Get the email verification status of the authenticated user.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'User not authenticated.',
                'error' => 'unauthenticated'
            ], 401);
        }

        return response()->json([
            'email_verified' => $user->hasVerifiedEmail(),
            'email_verified_at' => $user->email_verified_at,
            'email' => $user->email
        ]);
    }
}
