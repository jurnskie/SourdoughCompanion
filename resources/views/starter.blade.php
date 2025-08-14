@php
    $user = \App\Models\User::where('email', 'sourdough@localhost')->first() ?? \App\Models\User::first();
    $starter = $user ? $user->activeStarter() : null;
@endphp

<x-layouts.sourdough>
    <div class="px-4 py-6 space-y-6">
        <!-- Page Header -->
        <div class="text-center">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">My Starter</h1>
            @if(!$starter)
                <p class="text-gray-600 dark:text-gray-400">Get started with sourdough baking</p>
            @endif
        </div>

        @if($starter)
            <!-- Starter Status Cards -->
            <livewire:starter-status-card :starter="$starter" />
            <livewire:starter-phase-card :starter="$starter" />
            
            @if($starter->feedings->count() > 0)
                <livewire:last-feeding-card :starter="$starter" />
            @endif
            
            <livewire:starter-stats-card :starter="$starter" />
        @else
            <!-- No Starter View -->
            <div class="flex flex-col items-center justify-center py-12 px-4">
                <div class="w-20 h-20 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17 8C8 10 5.9 16.17 3.82 21.34l1.06.82C6.16 17.4 9 14 17 12V8zm0-2c0 .55.45 1 1 1s1-.45 1-1V3c0-.55-.45-1-1-1s-1 .45-1 1v3z"/>
                    </svg>
                </div>
                
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-2">No Active Starter</h2>
                <p class="text-gray-600 dark:text-gray-400 text-center mb-8 max-w-sm">
                    Create your first sourdough starter to begin your bread making journey!
                </p>
                
                <livewire:create-starter-form />
            </div>
        @endif
    </div>
</x-layouts.sourdough>