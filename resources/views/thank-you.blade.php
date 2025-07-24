<!-- resources/views/thankyou.blade.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Thank You!</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 min-h-screen flex items-center justify-center px-4">

<div class="bg-white rounded-lg shadow-lg max-w-md w-full p-8 text-center">
    <svg class="mx-auto mb-6 w-16 h-16 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"  xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
    </svg>

    <h1 class="text-3xl font-bold text-gray-900 mb-2">Thank you!</h1>
    <p class="text-gray-600 mb-6">Your document has been signed successfully.</p>

    <a href="/" class="inline-block bg-indigo-600 text-white px-6 py-3 rounded-md hover:bg-indigo-700 transition">
        Back to Home
    </a>
</div>

</body>
</html>
