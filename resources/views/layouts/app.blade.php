<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="format-detection" content="telephone=no">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap">
    <link rel="shortcut icon" href="{{ asset('personal-acc/favicon.ico') }}">
    @vite([
        'resources/personal-acc/css/style.css',
        'resources/personal-acc/js/app.js',
        'resources/js/personal-acc-blade.js',
    ])
    @stack('styles')
</head>
<body>
    @yield('content')
    @stack('scripts')
</body>
</html>
