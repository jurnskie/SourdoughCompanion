<div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Stats</h3>
    
    <div class="space-y-3">
        <div class="flex justify-between">
            <span class="text-sm text-gray-600 dark:text-gray-400">Phase</span>
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ ucfirst($starter->getCurrentPhase()) }}</span>
        </div>
        
        <div class="flex justify-between">
            <span class="text-sm text-gray-600 dark:text-gray-400">Age</span>
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $starter->created_at->diffForHumans() }}</span>
        </div>
        
        <div class="flex justify-between">
            <span class="text-sm text-gray-600 dark:text-gray-400">Total Feedings</span>
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $starter->feedings->count() }}</span>
        </div>
        
        <div class="flex justify-between">
            <span class="text-sm text-gray-600 dark:text-gray-400">Flour Type</span>
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ ucwords($starter->flour_type) }}</span>
        </div>
        
        @if($starter->notes)
            <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                <span class="text-sm text-gray-600 dark:text-gray-400">Notes</span>
                <p class="text-sm font-medium text-gray-900 dark:text-white mt-1">{{ $starter->notes }}</p>
            </div>
        @endif
    </div>
</div>
