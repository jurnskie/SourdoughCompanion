@php
    $recentFeedings = $starter->feedings()->latest()->take(3)->get();
@endphp

<div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Feedings</h3>
    
    @if($recentFeedings->count() > 0)
        <div class="space-y-4">
            @foreach($recentFeedings as $feeding)
                <div class="flex items-center justify-between py-2 {{ !$loop->last ? 'border-b border-gray-200 dark:border-gray-700' : '' }}">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $feeding->created_at->diffForHumans() }}
                        </p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            {{ $feeding->starter_amount }}g + {{ $feeding->flour_amount }}g + {{ $feeding->water_amount }}g
                        </p>
                    </div>
                    
                    <div class="text-right">
                        <span class="inline-block px-2 py-1 text-xs font-medium bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 rounded">
                            {{ $feeding->ratio }}
                        </span>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ $feeding->hydration_percentage }}% hydration
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
        
        @if($starter->feedings()->count() > 3)
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('history') }}" 
                   class="text-sm text-orange-600 dark:text-orange-400 hover:text-orange-700 dark:hover:text-orange-300 font-medium"
                   wire:navigate>
                    View all {{ $starter->feedings()->count() }} feedings →
                </a>
            </div>
        @endif
    @else
        <p class="text-gray-500 dark:text-gray-400 text-center py-4">
            No feedings recorded yet. Feed your starter to see history here.
        </p>
    @endif
</div>
