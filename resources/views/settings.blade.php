<x-layouts.app>
    <div class="mx-auto max-w-4xl p-6">
        <div class="space-y-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Settings</h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Manage your account settings and preferences.
                </p>
            </div>

            <div class="grid gap-8 lg:grid-cols-3">
                <!-- Settings Navigation -->
                <div class="lg:col-span-1">
                    <nav class="space-y-1">
                        <a href="#" class="flex items-center px-3 py-2 text-sm font-medium text-orange-600 bg-orange-50 dark:bg-orange-900/20 dark:text-orange-400 rounded-md">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Profile
                        </a>
                        <a href="#" class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-md">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            Security
                        </a>
                        <a href="#" class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-md">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7H4l5-5v5z" />
                            </svg>
                            Notifications
                        </a>
                    </nav>
                </div>

                <!-- Settings Content -->
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                        <livewire:settings.profile />
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>