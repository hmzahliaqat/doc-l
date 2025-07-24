<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Link Expired</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

<div class="bg-white p-8 rounded-lg shadow-lg text-center max-w-md">
    <div class="text-red-500">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m0-4h.01M12 8v4m0 4h.01M12 20h.01M4 4l16 16" />
        </svg>
    </div>
    <h2 class="text-2xl font-semibold text-gray-800 mb-2">Link Expired</h2>
    <p class="text-gray-600 mb-4">
        The link you followed has expired or is no longer valid.
    </p>
    <a href="{{ url('/') }}" class="inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
        Go to Homepage
    </a>
</div>

</body>
</html>
