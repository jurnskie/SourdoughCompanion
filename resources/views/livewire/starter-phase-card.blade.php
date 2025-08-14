<div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Current Phase</h3>
    
    <div class="flex items-center justify-between mb-4">
        <div class="flex-1">
            <h4 class="text-xl font-semibold text-gray-900 dark:text-white mb-1">
                {{ ucfirst($starter->getCurrentPhase()) }} Phase
            </h4>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                @if($starter->getCurrentPhase() === 'creation')
                    Daily feeding required • Focus on consistency
                @else
                    Flexible feeding schedule • Ready for baking
                @endif
            </p>
        </div>
        
        <div class="text-right">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $starter->feedings->count() }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">feedings</div>
        </div>
    </div>

    @if($starter->getCurrentPhase() === 'creation')
        <!-- Creation phase progress -->
        <div class="space-y-3">
            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                <span>Days Progress</span>
                <span>{{ $starter->getCurrentDay() }}/7</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div class="bg-orange-500 h-2 rounded-full" 
                     style="width: {{ min(100, ($starter->getCurrentDay() / 7) * 100) }}%"></div>
            </div>
            
            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                <span>Feedings Progress</span>
                <span>{{ $starter->feedings->count() }}/5</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div class="bg-orange-500 h-2 rounded-full" 
                     style="width: {{ min(100, ($starter->feedings->count() / 5) * 100) }}%"></div>
            </div>
        </div>
    @else
        <!-- Maintenance phase status -->
        <div class="flex items-center space-x-2 text-green-600 dark:text-green-400">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span class="text-sm font-medium">Active and ready for use ✨</span>
        </div>
    @endif
</div>
