<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no" />

<title>{{ $title ?? config('app.name') }}</title>

<!-- PWA Meta Tags -->
<meta name="application-name" content="Sourdough Companion" />
<meta name="apple-mobile-web-app-title" content="Sourdough" />
<meta name="description" content="Track your sourdough starter and manage feedings" />
<meta name="theme-color" content="#ea580c" />
<meta name="background-color" content="#18181b" />

<!-- Apple PWA Meta Tags -->
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
<meta name="apple-touch-fullscreen" content="yes" />

<!-- Microsoft PWA Meta Tags -->
<meta name="msapplication-TileColor" content="#ea580c" />
<meta name="msapplication-config" content="/browserconfig.xml" />

<!-- Icons -->
<link rel="icon" href="/favicon.ico" sizes="32x32" />
<link rel="icon" href="/favicon.svg" type="image/svg+xml" />
<link rel="apple-touch-icon" href="/apple-touch-icon.png" />

<!-- PWA Manifest -->
<link rel="manifest" href="/manifest.json" />

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
