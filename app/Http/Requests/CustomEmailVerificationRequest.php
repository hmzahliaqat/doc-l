<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

class CustomEmailVerificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Always authorize the request since we're handling authentication manually
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            //
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->hasValidSignature()) {
                $validator->errors()->add('signature', 'Invalid signature.');
            }
        });
    }

    /**
     * Fulfill the email verification request.
     */
    public function fulfill()
    {
        // Get the user by ID from the route parameter
        $user = User::find($this->route('id'));

        if (!$user) {
            return false;
        }

        // Check if the hash matches the user's email
        if (!hash_equals(sha1($user->email), (string) $this->route('hash'))) {
            return false;
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return true;
    }

    /**
     * Get the user from the request.
     *
     * @param string|null $guard
     * @return \App\Models\User|null
     */
    public function user($guard = null)
    {
        return User::find($this->route('id'));
    }
}
