<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email Templates</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
        }
        .container {
            max-width: 800px;
        }
        .card {
            margin-bottom: 20px;
        }
        .variable-row {
            margin-bottom: 10px;
        }
        #response {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
            display: none;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Test Email Templates</h1>

        <div class="card">
            <div class="card-header">
                <h5>Send Test Email</h5>
            </div>
            <div class="card-body">
                <form id="testEmailForm">
                    <div class="mb-3">
                        <label for="template_id" class="form-label">Select Template</label>
                        <select class="form-select" id="template_id" name="template_id" required>
                            <option value="">Select a template</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}">{{ $template->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="to_email" class="form-label">Recipient Email</label>
                        <input type="email" class="form-control" id="to_email" name="to_email" required>
                    </div>

                    <div id="variables-container">
                        <h6>Template Variables</h6>
                        <p class="text-muted">Select a template to see its variables</p>
                    </div>

                    <button type="submit" class="btn btn-primary">Send Test Email</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5>Test All Templates</h5>
            </div>
            <div class="card-body">
                <form id="testAllForm">
                    <div class="mb-3">
                        <label for="all_to_email" class="form-label">Recipient Email</label>
                        <input type="email" class="form-control" id="all_to_email" name="to_email" required>
                    </div>

                    <button type="submit" class="btn btn-warning">Send All Templates</button>
                </form>
            </div>
        </div>

        <div id="response"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Load template variables when a template is selected
            $('#template_id').change(function() {
                const templateId = $(this).val();
                if (!templateId) {
                    $('#variables-container').html('<h6>Template Variables</h6><p class="text-muted">Select a template to see its variables</p>');
                    return;
                }

                $.get(`/api/email-templates/${templateId}`, function(template) {
                    let html = '<h6>Template Variables</h6>';

                    if (template.variables && template.variables.length > 0) {
                        template.variables.forEach(variable => {
                            html += `
                                <div class="variable-row">
                                    <label for="var_${variable.variable_name}" class="form-label">${variable.display_name}</label>
                                    <input type="text" class="form-control" id="var_${variable.variable_name}"
                                           name="variables[${variable.variable_name}]"
                                           placeholder="${variable.default_value || ''}">
                                </div>
                            `;
                        });
                    } else {
                        html += '<p class="text-muted">No variables defined for this template</p>';
                    }

                    $('#variables-container').html(html);
                }).fail(function() {
                    $('#variables-container').html('<h6>Template Variables</h6><p class="text-danger">Failed to load template variables</p>');
                });
            });

            // Handle test email form submission
            $('#testEmailForm').submit(function(e) {
                e.preventDefault();

                const formData = $(this).serialize();

                $.ajax({
                    url: '{{ route("test.email.send") }}',
                    type: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        showResponse(response.message, true);
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || 'An error occurred while sending the test email';
                        showResponse(message, false);
                    }
                });
            });

            // Handle test all templates form submission
            $('#testAllForm').submit(function(e) {
                e.preventDefault();

                const formData = $(this).serialize();

                $.ajax({
                    url: '{{ route("test.email.all") }}',
                    type: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        let message = 'Results:<br>';

                        for (const [template, result] of Object.entries(response.results)) {
                            message += `<strong>${template}</strong>: ${result.success ? 'Success' : 'Failed'} - ${result.message}<br>`;
                        }

                        showResponse(message, true);
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || 'An error occurred while sending the test emails';
                        showResponse(message, false);
                    }
                });
            });

            // Show response message
            function showResponse(message, success) {
                const $response = $('#response');
                $response.removeClass('success error').addClass(success ? 'success' : 'error');
                $response.html(message).show();

                // Scroll to response
                $('html, body').animate({
                    scrollTop: $response.offset().top
                }, 500);
            }
        });
    </script>
</body>
</html>
