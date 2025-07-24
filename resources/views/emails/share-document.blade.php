<section class="max-w-2xl px-6 py-8 mx-auto bg-white dark:bg-gray-900">
    <header>
        <a href="#">
            <img class="w-auto h-7 sm:h-8" src="https://merakiui.com/images/full-logo.svg" alt="Logo">
        </a>
    </header>

    <main class="mt-8">
        <h2 class="text-gray-700 dark:text-gray-200">Hello,</h2>

        @isset($type)
            @if ($type == 'reminder')
                <p class="mt-2 leading-loose text-gray-600 dark:text-gray-300">
                    Reminder: You have not signed the document yet.
                </p>
                <a href="{{ url('document/' . $document_pdf_id . '/' . $employee_id . '/preview') }}"
                   class="inline-block px-6 py-2 mt-4 text-sm font-medium tracking-wider text-white capitalize transition-colors duration-300 transform bg-blue-600 rounded-lg hover:bg-blue-500 focus:outline-none focus:ring focus:ring-blue-300 focus:ring-opacity-80">
                    Click here to sign
                </a>
            @else
                <p class="mt-2 leading-loose text-gray-600 dark:text-gray-300">
                    You have received a document to view.
                </p>
                <a href="{{ url('api/documents/' .$shared_document_id. '/' . $document_pdf_id . '/' . $employee_id . '/employee-view') }}"
                   class="inline-block px-6 py-2 mt-4 text-sm font-medium tracking-wider text-white capitalize transition-colors duration-300 transform bg-blue-600 rounded-lg hover:bg-blue-500 focus:outline-none focus:ring focus:ring-blue-300 focus:ring-opacity-80">
                    View Document
                </a>
            @endif
        @endisset

        <p class="mt-8 text-gray-600 dark:text-gray-300">
            Thanks, <br>
            Your Company Team
        </p>
    </main>

    <footer class="mt-8">
        <p class="text-gray-500 dark:text-gray-400">
            This message was sent to <a href="#" class="text-blue-600 hover:underline dark:text-blue-400" target="_blank">your-email@example.com</a>.
            If you no longer wish to receive such emails, you can <a href="#" class="text-blue-600 hover:underline dark:text-blue-400">unsubscribe</a> or <a href="#" class="text-blue-600 hover:underline dark:text-blue-400">manage preferences</a>.
        </p>

        <p class="mt-3 text-gray-500 dark:text-gray-400">© {{ now()->year }} Your Company. All Rights Reserved.</p>
    </footer>
</section>
