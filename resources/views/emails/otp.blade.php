<x-mail::message>
# Your One-Time Password (OTP)

You have requested a one-time password for verification. Please use the following code:

<div style="text-align: center; font-size: 24px; font-weight: bold; padding: 20px; background-color: #f5f5f5; margin: 20px 0; letter-spacing: 5px;">
    {{ $otpCode }}
</div>

This code will expire in 10 minutes. If you did not request this code, please ignore this email.

<x-mail::panel>
For security reasons, never share this code with anyone. Our team will never ask for your OTP.
</x-mail::panel>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
