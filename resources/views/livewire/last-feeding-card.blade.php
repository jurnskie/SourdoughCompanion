@php
    $lastFeeding = $starter->feedings()->latest()->first();
@endphp

<div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Last Feeding</h3>
    
    @if($lastFeeding)
        <div class="space-y-3">
            <div>
                <p class="text-base font-medium text-gray-900 dark:text-white">
                    {{ $lastFeeding->created_at->diffForHumans() }}
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $lastFeeding->created_at->format('M j, Y \\a\\t g:i A') }}
                </p>
            </div>
            
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                    {{ $lastFeeding->starter_amount }}g starter + {{ $lastFeeding->flour_amount }}g flour + {{ $lastFeeding->water_amount }}g water
                </p>
                
                <div class="flex justify-between items-center">
                    <span class="inline-block px-2 py-1 text-xs font-medium bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 rounded">
                        Ratio: {{ $lastFeeding->ratio }}
                    </span>
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        Hydration: {{ $lastFeeding->hydration_percentage }}%
                    </span>
                </div>
            </div>
        </div>
    @else
        <p class="text-gray-500 dark:text-gray-400">No feedings recorded yet.</p>
    @endif
</div>
