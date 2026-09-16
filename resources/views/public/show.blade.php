<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Form</title>
    <style>
        :root { --ink: #1B1F23; --ink-soft: #5B6570; --border: #D8DCE1; --accent: #33418E; --error: #B3261E; --success: #1E7145; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: var(--ink); background: #fff; }
        form { max-width: 480px; margin: 0 auto; }
        .field-wrapper { margin-bottom: 18px; }
        label { display: block; font-size: 14px; font-weight: 500; margin-bottom: 5px; }
        input[type=text], input[type=email], input[type=number], input[type=date], select {
            width: 100%; padding: 9px 10px; border: 1px solid var(--border); border-radius: 4px; font-size: 15px; font-family: inherit;
        }
        input:focus, select:focus, button:focus { outline: 2px solid var(--accent); outline-offset: 1px; }
        small { display: block; color: var(--ink-soft); margin-top: 4px; font-size: 12px; }
        .inline-option { display: inline-flex; align-items: center; gap: 6px; margin-right: 16px; font-weight: 400; font-size: 14px; }
        .inline-option input { width: auto; }
        button[type=submit] {
            background: var(--accent); color: #fff; border: none; border-radius: 4px; padding: 10px 20px; font-size: 15px; font-weight: 500; cursor: pointer;
        }
        button[type=submit]:disabled { opacity: 0.6; cursor: default; }
        #form-message { font-size: 14px; margin-top: 12px; }
        #form-message.success { color: var(--success); }
        #form-message.error { color: var(--error); }
        .hp-field { position: absolute; left: -9999px; top: -9999px; }
    </style>
</head>
<body>
    <div id="public-form-root">
        <p>Loading form…</p>
    </div>

    <script>
        window.__ACCOUNT_API_KEY__ = @json($accountApiKey);
        window.__SLUG__ = @json($slug);
    </script>
    <script src="{{ asset('js/public-form.js') }}"></script>
</body>
</html>
