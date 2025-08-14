<div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="flex items-start justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Status</h3>
        <span class="text-sm text-gray-500 dark:text-gray-400">Day {{ $starter->getCurrentDay() }}</span>
    </div>

    <div class="flex items-center justify-between">
        <div class="flex-1">
            <h4 class="text-xl font-semibold text-gray-900 dark:text-white mb-1">{{ $starter->name }}</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                {{ $starter->flour_type }} • {{ $starter->created_at->diffForHumans() }}
            </p>
        </div>

        <div class="text-right">
            <div class="flex items-center space-x-1 mb-1">
                @if($this->healthStatus['color'] === 'green')
                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                @elseif($this->healthStatus['color'] === 'orange')
                    <svg class="w-4 h-4 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                @elseif($this->healthStatus['color'] === 'red')
                    <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                @else
                    <svg class="w-4 h-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                    </svg>
                @endif
                <span class="text-sm font-medium text-{{ $this->healthStatus['color'] }}-600 dark:text-{{ $this->healthStatus['color'] }}-400">
                    {{ $this->healthStatus['message'] }}
                </span>
            </div>
        </div>
    </div>

    @if($this->canReset['can_reset'] ?? false)
        @php
            $isRecommended = $this->canReset['recommended_reset'] ?? false;
            $buttonClass = $isRecommended
                ? 'text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30'
                : 'text-orange-600 hover:text-orange-700 dark:text-orange-400 dark:hover:text-orange-300 bg-orange-50 hover:bg-orange-100 dark:bg-orange-900/20 dark:hover:bg-orange-900/30';
        @endphp

        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    @if($isRecommended)
                        <p class="text-sm text-orange-600 dark:text-orange-400">Reset recommended</p>
                        <p class="text-xs text-gray-500 dark:text-gray-500">{{ round($this->canReset['days_since_feeding'] ?? 0) }} days since last feeding</p>
                    @else
                        <p class="text-sm text-gray-600 dark:text-gray-400">Starter management</p>
                        <p class="text-xs text-gray-500 dark:text-gray-500">Reset anytime to start fresh</p>
                    @endif
                </div>
                <flux:modal.trigger name="reset-starter">
                    <flux:button
                        size="sm"
                        :variant="$isRecommended ? 'danger' : 'filled'"
                        icon="arrow-path">
                        Reset Starter
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>
    @endif

    <flux:modal name="reset-starter" class="max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">Reset Starter?</flux:heading>

            @php
                $isHealthy = $this->canReset['is_healthy'] ?? false;
            @endphp

            <div class="space-y-4">
                @php
                    $warningBg = $isHealthy ? 'bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-800' : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800';
                @endphp

                <div class="rounded-xl p-4 border {{ $warningBg }}">
                    <flux:heading size="sm" class="mb-2">
                        {{ $this->canReset['warning_message'] ?? 'Are you sure you want to reset this starter?' }}
                    </flux:heading>

                    <flux:text size="sm" variant="muted">
                        <strong>Current Status:</strong> {{ $this->canReset['health_status']['message'] ?? 'Unknown' }}<br>
                        <strong>Days since feeding:</strong> {{ round($this->canReset['days_since_feeding'] ?? 0) }}<br>
                        <strong>Age:</strong> {{ $starter->getCurrentDay() }} days<br>
                        <strong>Total feedings:</strong> {{ $starter->feedings()->count() }}
                    </flux:text>
                </div>

                <flux:text size="sm" variant="muted">
                    This will archive your current starter and create a fresh one. The current starter's history will be preserved in the notes for future reference.
                </flux:text>
            </div>

            <div class="flex justify-end space-x-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>

                <flux:button
                    wire:click="resetStarter"
                    :variant="$isHealthy ? 'filled' : 'danger'">
                    {{ $isHealthy ? 'Reset Anyway' : 'Reset Starter' }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
