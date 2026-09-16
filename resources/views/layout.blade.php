<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Form Builder')</title>
    <style>
        :root {
            --ink: #1B1F23;
            --ink-soft: #5B6570;
            --bg: #F5F6F8;
            --panel: #FFFFFF;
            --border: #E1E4E8;
            --accent: #33418E;
            --accent-hover: #283570;
            --danger: #B3261E;
            --success-bg: #E6F4EA;
            --success-text: #1E7145;
            --draft-bg: #FCF1D8;
            --draft-text: #8A6D1D;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--bg);
            color: var(--ink);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 15px;
            line-height: 1.5;
        }
        header.topbar {
            background: var(--panel);
            border-bottom: 1px solid var(--border);
            padding: 14px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        header.topbar .brand { font-weight: 600; letter-spacing: -0.01em; }
        header.topbar a { color: var(--ink-soft); text-decoration: none; font-size: 13px; }
        header.topbar a:hover { color: var(--accent); }
        main {
            max-width: 880px;
            margin: 0 auto;
            padding: 32px 24px 64px;
        }
        h1 { font-size: 22px; margin: 0 0 4px; }
        h2 { font-size: 17px; margin: 0 0 12px; }
        p.lead { color: var(--ink-soft); margin: 0 0 24px; }
        .panel {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 20px;
        }
        label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 4px; }
        input[type=text], input[type=email], input[type=number], input[type=date], select, textarea {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid var(--border);
            border-radius: 4px;
            font-size: 14px;
            font-family: inherit;
            background: #fff;
            color: var(--ink);
        }
        input:focus, select:focus, textarea:focus, button:focus {
            outline: 2px solid var(--accent);
            outline-offset: 1px;
        }
        button {
            border: none;
            border-radius: 4px;
            padding: 9px 16px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
        }
        button.primary { background: var(--accent); color: #fff; }
        button.primary:hover { background: var(--accent-hover); }
        button.secondary { background: #EEF0F3; color: var(--ink); }
        button.secondary:hover { background: #E2E5EA; }
        button.danger-link { background: none; color: var(--danger); padding: 4px 0; font-weight: 400; text-decoration: underline; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 10px 8px; border-bottom: 1px solid var(--border); font-size: 14px; }
        th { color: var(--ink-soft); font-weight: 500; font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 12px; font-weight: 500; }
        .badge.published { background: var(--success-bg); color: var(--success-text); }
        .badge.draft { background: var(--draft-bg); color: var(--draft-text); }
        .actions a, .actions button { margin-right: 12px; font-size: 13px; }
        .field-key { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; background: #EEF0F3; padding: 2px 5px; border-radius: 3px; font-size: 13px; }
    </style>
</head>
<body>
    <header class="topbar">
        <span class="brand">Form Builder</span>
        @if(session('account_id'))
            <a href="{{ route('builder.choose-account') }}">Switch account</a>
        @endif
    </header>
    <main>
        @yield('content')
    </main>
</body>
</html>
