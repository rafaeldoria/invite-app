<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>
        @php($meta = $page['props']['meta'] ?? null)
        @if (is_array($meta))
            @isset($meta['description'])
                <meta name="description" content="{{ $meta['description'] }}">
                <meta property="og:description" content="{{ $meta['description'] }}">
            @endisset
            @isset($meta['title'])
                <meta property="og:title" content="{{ $meta['title'] }}">
            @endisset
            @isset($meta['url'])
                <meta property="og:url" content="{{ $meta['url'] }}">
            @endisset
            @if (! empty($meta['image']))
                <meta property="og:image" content="{{ $meta['image'] }}">
            @endif
            <meta property="og:type" content="website">
        @endif

        <script>
            (() => {
                let stored = null;

                try {
                    stored = localStorage.getItem('invite-app-theme');
                } catch {
                    // Fall back to the system preference when storage is blocked.
                }

                const mode = ['light', 'dark', 'system'].includes(stored ?? '') ? stored : 'system';
                const theme = mode === 'system'
                    ? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                    : mode;
                document.documentElement.dataset.theme = theme;
                document.documentElement.style.colorScheme = theme;
            })();
        </script>

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx'])
        @inertiaHead
    </head>
    <body class="antialiased">
        @inertia
    </body>
</html>
