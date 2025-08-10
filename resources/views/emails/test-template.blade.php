<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Test SuperAdminSettings Integration</title>
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
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        .debug-info {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            padding: 15px;
            margin-top: 20px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        @if(isset($appSettings['app_logo_url']))
            <img class="logo" src="{{ $appSettings['app_logo_url'] }}" alt="{{ $appSettings['app_name'] }} Logo">
        @endif
        <h1>Test Template</h1>
    </div>

    <div class="content">
        <p>This is a test template to verify that SuperAdminSettings are properly integrated.</p>

        <div class="debug-info">
            <h3>Debug Information:</h3>
            <p><strong>App Name:</strong> {{ $appSettings['app_name'] ?? 'Not set' }}</p>
            <p><strong>App Logo:</strong> {{ $appSettings['app_logo'] ?? 'Not set' }}</p>
            <p><strong>App Logo URL:</strong> {{ $appSettings['app_logo_url'] ?? 'Not set' }}</p>
        </div>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ $appSettings['app_name'] }}. All rights reserved.</p>
    </div>
</body>
</html>
