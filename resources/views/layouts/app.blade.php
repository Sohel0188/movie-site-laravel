<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'StreamVault — Movies, TV Shows & Episodes')</title>
    <meta name="description" content="Stream 90,000+ movies, 19,000+ TV shows and 478,000+ episodes for free.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-bg text-white antialiased">
    @include('components.navbar')

    <main class="pt-14 pb-16 w-full min-w-0 overflow-x-hidden max-w-[1400px] mx-auto px-4 sm:px-6">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
