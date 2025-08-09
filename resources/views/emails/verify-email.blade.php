<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Your Email Address</title>
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
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
        }
        .button {
            display: inline-block;
            background-color: #3490dc;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            margin: 20px 0;
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
        <h1>Verify Your Email Address</h1>
    </div>

    <div class="content">
        <p>Hello {{ $user->name }},</p>

        <p>Thank you for registering! Please click the button below to verify your email address:</p>

        <div style="text-align: center;">
            <a href="{{ $verificationUrl }}" class="button">Verify Email Address</a>
        </div>

        <p>If you did not create an account, no further action is required.</p>

        <p>If you're having trouble clicking the "Verify Email Address" button, copy and paste the URL below into your web browser:</p>

        <p style="word-break: break-all;">{{ $verificationUrl }}</p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} PDF Signature. All rights reserved.</p>
    </div>
</body>
</html>
