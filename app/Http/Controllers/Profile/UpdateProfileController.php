<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UpdateProfileController extends Controller
{

    public function profileInformation(Request $request)
    {

      $user =  User::where('email', $request->email)->firstOrFail();
      $user->update($request->all());

      return response()->json($user, 200);
    }


    public function updatePassword(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'current_password' => ['required', 'string'],
                'email' => ['required', 'email'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
                'password_confirmation' => ['required', 'string'],
            ]);

            // Get the authenticated user or find user by email
            $user = Auth::user();

            // If no authenticated user, find by email (optional - depends on your use case)
            if (!$user) {
                $user = \App\Models\User::where('email', $validated['email'])->first();

                if (!$user) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User not found'
                    ], 404);
                }
            }

            // Verify current password
            if (!Hash::check($validated['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['The current password is incorrect.']
                ]);
            }

            // Verify email matches (security check)
            if ($user->email !== $validated['email']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email does not match the current user'
                ], 422);
            }

            // Update the password
            $user->update([
                'password' => Hash::make($validated['password'])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully'
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the password'
            ], 500);
        }
    }
}
