<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Sourdough Companion') }} - Login</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxStyles
</head>
<body class="font-sans antialiased bg-gray-50 dark:bg-gray-900">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
        <!-- Logo -->
        <div class="mb-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-amber-600 rounded-lg flex items-center justify-center mr-3">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17 8C8 10 5.9 16.17 3.82 21.34l1.06.82C6.16 17.4 9 14 17 12V8zm0-2c0 .55.45 1 1 1s1-.45 1-1V3c0-.55-.45-1-1-1s-1 .45-1 1v3z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Sourdough Companion
                </h1>
            </div>
        </div>

        <!-- Login Form -->
        <div class="w-full sm:max-w-md bg-white dark:bg-gray-800 shadow-lg rounded-lg px-6 py-8">
            <div class="mb-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white text-center">
                    Sign in to your account
                </h2>
            </div>

            <!-- Session Status -->
            @if (session('status'))
                <flux:callout variant="success" class="mb-4">
                    {{ session('status') }}
                </flux:callout>
            @endif

            <form method="POST" action="{{ route('login.store') }}">
                @csrf

                <!-- Email Address -->
                <div class="mb-4">
                    <flux:field>
                        <flux:label for="email">Email</flux:label>
                        <flux:input 
                            id="email" 
                            name="email" 
                            type="email" 
                            :value="old('email')" 
                            required 
                            autofocus 
                            autocomplete="username"
                            placeholder="Enter your email address"
                        />
                        <flux:error name="email" />
                    </flux:field>
                </div>

                <!-- Password -->
                <div class="mb-4">
                    <flux:field>
                        <flux:label for="password">Password</flux:label>
                        <flux:input 
                            id="password" 
                            name="password" 
                            type="password" 
                            required 
                            autocomplete="current-password"
                            placeholder="Enter your password"
                        />
                        <flux:error name="password" />
                    </flux:field>
                </div>

                <!-- Remember Me -->
                <div class="mb-6">
                    <flux:field>
                        <flux:checkbox id="remember" name="remember" />
                        <flux:label for="remember">Remember me</flux:label>
                    </flux:field>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-center">
                    <flux:button type="submit" variant="primary" class="w-full">
                        <div class="flex items-center justify-center">
                            <flux:icon name="arrow-right-circle" class="w-4 h-4 mr-2" />
                            Sign In
                        </div>
                    </flux:button>
                </div>
            </form>

            <!-- Footer -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Access is managed by your administrator.
                </p>
            </div>
        </div>
    </div>

    @fluxScripts
</body>
</html>