<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use App\Models\OtpCode;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

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

    /**
     * Get OTP settings for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getOtpSettings(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get or create the settings record for the authenticated user
        $settings = $user->setting;
        if (!$settings) {
            $settings = new Setting();
            $settings->user_id = $user->id;
            $settings->otp_enabled = false; // Default value
            $settings->save();
        }

        return response()->json([
            'otp_enabled' => $settings->otp_enabled
        ]);
    }

    /**
     * Update OTP settings for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateOtpSettings(Request $request): JsonResponse
    {
        $request->validate([
            'otp_enabled' => 'required|boolean',
        ]);

        $user = $request->user();

        // Get or create the settings record for the authenticated user
        $settings = $user->setting;
        if (!$settings) {
            $settings = new Setting();
            $settings->user_id = $user->id;
        }

        // Update the OTP settings
        $settings->otp_enabled = $request->otp_enabled;
        $settings->save();

        return response()->json([
            'message' => 'OTP settings updated successfully',
            'otp_enabled' => $settings->otp_enabled
        ]);
    }

    /**
     * Request an OTP code for the given email.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function requestOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = $request->email;

        // Find user by email (if exists)
        $user = User::where('email', $email)->first();
        $userId = $user ? $user->id : null;

        // Generate OTP code
        $otpCode = OtpCode::createForEmail($email, $userId);

        // Send OTP email
        Mail::to($email)->send(new OtpMail($otpCode->otp_code, $email));

        return response()->json([
            'message' => 'OTP code sent successfully',
            'email' => $email
        ]);
    }

    /**
     * Verify an OTP code for the given email.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'otp_code' => 'required|string|size:4',
        ]);

        $email = $request->email;
        $code = $request->otp_code;

        // Find the most recent valid OTP code for this email
        $otpCode = OtpCode::where('email', $email)
            ->where('verified', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$otpCode) {
            return response()->json([
                'message' => 'Invalid or expired OTP code',
                'verified' => false
            ], 400);
        }

        // Verify the OTP code
        $verified = $otpCode->verify($code);

        return response()->json([
            'message' => $verified ? 'OTP code verified successfully' : 'Invalid OTP code',
            'verified' => $verified
        ], $verified ? 200 : 400);
    }
}
