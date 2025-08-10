<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your One-Time Password (OTP)</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            max-width: 200px;
            margin-bottom: 15px;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
        }
        .otp-code {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            padding: 20px;
            background-color: #f5f5f5;
            margin: 20px 0;
            letter-spacing: 5px;
            border-radius: 5px;
        }
        .panel {
            background-color: #e8f4ff;
            border-left: 4px solid #3490dc;
            padding: 15px;
            margin: 20px 0;
            border-radius: 3px;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        @if(isset($appSettings['app_logo_url']))
            <img class="logo" src="{{ $appSettings['app_logo_url'] }}" alt="{{ $appSettings['app_name'] }} Logo">
        @endif
        <h1>Your One-Time Password (OTP)</h1>
    </div>

    <div class="content">
        <p>You have requested a one-time password for verification. Please use the following code:</p>

        <div class="otp-code">
            {{ $otpCode }}
        </div>

        <p>This code will expire in 10 minutes. If you did not request this code, please ignore this email.</p>

        <div class="panel">
            <p>For security reasons, never share this code with anyone. Our team will never ask for your OTP.</p>
        </div>

        <p>Thanks,<br>
        {{ $appSettings['app_name'] }}</p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ $appSettings['app_name'] }}. All rights reserved.</p>
    </div>
</body>
</html>
