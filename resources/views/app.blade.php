<!DOCTYPE html>
<html lang="en-US" data-theme="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

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
