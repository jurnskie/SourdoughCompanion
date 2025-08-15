<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
    </head>
    <body class="min-h-screen bg-gray-50 dark:bg-zinc-900">
        <div class="flex flex-col h-screen">
            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto pb-20">
                {{ $slot }}
            </main>
            
            <!-- iOS-style Tab Bar -->
            <nav class="fixed bottom-0 left-0 right-0 bg-white dark:bg-zinc-800 border-t border-gray-200 dark:border-zinc-700 px-4 py-2">
                <div class="flex justify-around items-center">
                    <!-- Starter Tab -->
                    <a href="{{ route('starter') }}" 
                       class="flex flex-col items-center space-y-1 px-3 py-2 rounded-lg {{ request()->routeIs('starter') ? 'text-orange-600 dark:text-orange-400' : 'text-gray-600 dark:text-gray-400' }}"
                       wire:navigate>
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 8C8 10 5.9 16.17 3.82 21.34l1.06.82C6.16 17.4 9 14 17 12V8zm0-2c0 .55.45 1 1 1s1-.45 1-1V3c0-.55-.45-1-1-1s-1 .45-1 1v3z"/>
                        </svg>
                        <span class="text-xs font-medium">Starter</span>
                    </a>
                    
                    <!-- Feeding Tab -->
                    <a href="{{ route('feeding') }}" 
                       class="flex flex-col items-center space-y-1 px-3 py-2 rounded-lg {{ request()->routeIs('feeding') ? 'text-orange-600 dark:text-orange-400' : 'text-gray-600 dark:text-gray-400' }}"
                       wire:navigate>
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12,3.77L11.25,4.61C11.25,4.61 9.97,6.06 8.68,7.94C7.39,9.82 6,12.07 6,14.23A6,6 0 0,0 12,20.23A6,6 0 0,0 18,14.23C18,12.07 16.61,9.82 15.32,7.94C14.03,6.06 12.75,4.61 12.75,4.61L12,3.77Z"/>
                        </svg>
                        <span class="text-xs font-medium">Feeding</span>
                    </a>
                    
                    <!-- Recipe Tab -->
                    <a href="{{ route('recipe') }}" 
                       class="flex flex-col items-center space-y-1 px-3 py-2 rounded-lg {{ request()->routeIs('recipe') ? 'text-orange-600 dark:text-orange-400' : 'text-gray-600 dark:text-gray-400' }}"
                       wire:navigate>
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12,6C13.11,6 14,5.1 14,4C14,3.62 13.9,3.27 13.71,2.97L12,0L10.29,2.97C10.1,3.27 10,3.62 10,4C10,5.1 10.89,6 12,6M18,9H16L14.5,7H9.5L8,9H6A2,2 0 0,0 4,11V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V11A2,2 0 0,0 18,9Z"/>
                        </svg>
                        <span class="text-xs font-medium">Recipe</span>
                    </a>
                    
                    <!-- History Tab -->
                    <a href="{{ route('history') }}" 
                       class="flex flex-col items-center space-y-1 px-3 py-2 rounded-lg {{ request()->routeIs('history') ? 'text-orange-600 dark:text-orange-400' : 'text-gray-600 dark:text-gray-400' }}"
                       wire:navigate>
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M22,21H2V3H4V19H6V17H10V19H12V16H16V19H18V17H22V21M4,1H6V3H4V1M12,1H14V3H12V1M16,8H18V12H16V8M10,4H12V8H10V4M16,13H18V16H16V13Z"/>
                        </svg>
                        <span class="text-xs font-medium">History</span>
                    </a>
                </div>
            </nav>
        </div>

        <!-- Global Photo Modal -->
        <div id="photoModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center p-4 z-50 hidden">
            <div class="relative max-w-4xl max-h-full">
                <button onclick="closePhotoModal()" 
                        class="absolute top-2 right-2 bg-black bg-opacity-50 text-white rounded-full p-2 hover:bg-opacity-75 z-10">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
                <img id="modalPhoto" src="" alt="Feeding photo" class="max-w-full max-h-full object-contain rounded-lg">
                <p id="modalDate" class="text-white text-center mt-2 text-sm"></p>
            </div>
        </div>

        @fluxScripts
    </body>
</html>