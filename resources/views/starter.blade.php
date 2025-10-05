@php
    $user = \App\Models\User::where('email', 'sourdough@localhost')->first() ?? \App\Models\User::first();
    $starter = $user ? $user->activeStarter() : null;
@endphp

<x-layouts.sourdough>
    <div class="px-4 py-6 space-y-8">
        <!-- Page Header -->
        <div class="text-center">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">My Starters</h1>
            <p class="text-gray-600 dark:text-gray-400">Manage your sourdough starters and notifications</p>
        </div>

        <!-- Starter Management Component -->
        <livewire:starters-list />

        @if($starter)
            <!-- Active Starter Details Section -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-8">
                <div class="text-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Active Starter Details</h2>
                    <p class="text-gray-600 dark:text-gray-400">Showing details for your most recent starter: {{ $starter->name }}</p>
                </div>

                <!-- Starter Status Cards -->
                <div class="space-y-6">
                    <livewire:starter-status-card :starter="$starter" />
                    <livewire:starter-phase-card :starter="$starter" />
                    
                    @if($starter->feedings->count() > 0)
                        <livewire:last-feeding-card :starter="$starter" />
                    @endif
                    
                    <livewire:starter-stats-card :starter="$starter" />
                </div>
            </div>
        @endif
    </div>
</x-layouts.sourdough>