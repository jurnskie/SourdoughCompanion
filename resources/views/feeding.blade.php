@php
    $user = \App\Models\User::where('email', 'sourdough@localhost')->first() ?? \App\Models\User::first();
    $starter = $user ? $user->activeStarter() : null;
@endphp

<x-layouts.sourdough>
    <div class="px-4 py-6 space-y-6">
        <!-- Page Header -->
        <div class="text-center">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Feeding</h1>
            <p class="text-gray-600 dark:text-gray-400">Manage your starter's feeding schedule</p>
        </div>

        @if($starter)
            <!-- Feeding Content -->
            <livewire:starter-status-card :starter="$starter" />
            <livewire:feeding-status-card :starter="$starter" />
            
            @if($starter->feedings->count() > 0)
                <livewire:recent-feedings-card :starter="$starter" />
            @endif
        @else
            <!-- No Starter View -->
            <div class="flex flex-col items-center justify-center py-12 px-4">
                <div class="w-20 h-20 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12,3.77L11.25,4.61C11.25,4.61 9.97,6.06 8.68,7.94C7.39,9.82 6,12.07 6,14.23A6,6 0 0,0 12,20.23A6,6 0 0,0 18,14.23C18,12.07 16.61,9.82 15.32,7.94C14.03,6.06 12.75,4.61 12.75,4.61L12,3.77Z"/>
                    </svg>
                </div>
                
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-2">No Active Starter</h2>
                <p class="text-gray-600 dark:text-gray-400 text-center mb-8 max-w-sm">
                    Create a starter first to begin feeding!
                </p>
                
                <a href="{{ route('starter') }}" 
                   class="bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 px-8 rounded-xl transition duration-200"
                   wire:navigate>
                    Go to Starter
                </a>
            </div>
        @endif
    </div>
</x-layouts.sourdough>