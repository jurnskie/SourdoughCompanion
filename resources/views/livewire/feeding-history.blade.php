@if($starter)
    <div class="space-y-6">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Total Feedings -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-orange-100 dark:bg-orange-900">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12,3.77L11.25,4.61C11.25,4.61 9.97,6.06 8.68,7.94C7.39,9.82 6,12.07 6,14.23A6,6 0 0,0 12,20.23A6,6 0 0,0 18,14.23C18,12.07 16.61,9.82 15.32,7.94C14.03,6.06 12.75,4.61 12.75,4.61L12,3.77Z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Feedings</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $statistics['total_feedings'] }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Average Hydration -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9,2V8H7V10H9V22H11V10H13V8H11V2H9Z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Avg Hydration</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $statistics['average_hydration'] }}%</p>
                    </div>
                </div>
            </div>
            
            <!-- Consistency Score -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M22,21H2V3H4V19H6V17H10V19H12V16H16V19H18V17H22V21M4,1H6V3H4V1M12,1H14V3H12V1M16,8H18V12H16V8M10,4H12V8H10V4M16,13H18V16H16V13Z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Consistency</p>
                        <div class="flex items-center space-x-2">
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $statistics['consistency_score'] }}%</p>
                            @if($statistics['consistency_score'] >= 80)
                                <span class="text-green-500">Excellent</span>
                            @elseif($statistics['consistency_score'] >= 60)
                                <span class="text-yellow-500">Good</span>
                            @else
                                <span class="text-red-500">Needs Work</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Resource Usage -->
        @if($statistics['total_feedings'] > 0)
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Resource Usage</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Flour Consumed</h4>
                        <div class="flex items-center space-x-2">
                            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-amber-500 h-2 rounded-full" style="width: 75%"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $statistics['total_flour_used'] }}g</span>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Water Used</h4>
                        <div class="flex items-center space-x-2">
                            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-blue-500 h-2 rounded-full" style="width: 75%"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $statistics['total_water_used'] }}g</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        
        <!-- Feeding Timeline -->
        @if(!empty($feedings))
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Feeding Timeline</h3>
                
                <div class="space-y-4 max-h-96 overflow-y-auto">
                    @foreach($feedings as $feeding)
                        <div class="flex items-center space-x-4 p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900 rounded-full flex items-center justify-center">
                                    <span class="text-sm font-medium text-orange-600 dark:text-orange-400">{{ $feeding->day }}</span>
                                </div>
                            </div>
                            
                            @if($feeding->hasPhoto())
                                <div class="flex-shrink-0">
                                    <img src="{{ $feeding->photo_url }}" alt="Feeding photo" 
                                         class="w-16 h-16 object-cover rounded-lg cursor-pointer hover:opacity-80 transition"
                                         onclick="openPhotoModal('{{ $feeding->photo_url }}', '{{ $feeding->created_at->format('M j, Y \a\t g:i A') }}')">
                                </div>
                            @endif
                            
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $feeding->created_at->format('M j, Y \\a\\t g:i A') }}
                                    </p>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $feeding->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                
                                <div class="mt-1 flex items-center space-x-4">
                                    <span class="text-xs text-gray-600 dark:text-gray-400">
                                        {{ $feeding->starter_amount }}g + {{ $feeding->flour_amount }}g + {{ $feeding->water_amount }}g
                                    </span>
                                    
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200">
                                        {{ $feeding->ratio }}
                                    </span>
                                    
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $feeding->hydration_percentage }}% hydration
                                    </span>
                                    
                                    @if($feeding->hasPhoto())
                                        <span class="inline-flex items-center text-xs text-blue-600 dark:text-blue-400">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                                            </svg>
                                            Photo
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                @if($feedings->count() > 10)
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Showing all {{ $feedings->count() }} feedings
                        </p>
                    </div>
                @endif
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="text-center py-8">
                    <svg class="w-12 h-12 text-gray-400 dark:text-gray-500 mx-auto mb-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M22,21H2V3H4V19H6V17H10V19H12V16H16V19H18V17H22V21M4,1H6V3H4V1M12,1H14V3H12V1M16,8H18V12H16V8M10,4H12V8H10V4M16,13H18V16H16V13Z"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No Feedings Yet</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">Start feeding your starter to see detailed history and statistics here.</p>
                    <a href="{{ route('feeding') }}" 
                       class="inline-flex items-center px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white font-medium rounded-lg transition duration-200"
                       wire:navigate>
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12,3.77L11.25,4.61C11.25,4.61 9.97,6.06 8.68,7.94C7.39,9.82 6,12.07 6,14.23A6,6 0 0,0 12,20.23A6,6 0 0,0 18,14.23C18,12.07 16.61,9.82 15.32,7.94C14.03,6.06 12.75,4.61 12.75,4.61L12,3.77Z"/>
                        </svg>
                        Feed Starter
                    </a>
                </div>
            </div>
        @endif
    </div>
@else
    <!-- No Starter State -->
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="text-center py-8">
            <svg class="w-12 h-12 text-gray-400 dark:text-gray-500 mx-auto mb-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17 8C8 10 5.9 16.17 3.82 21.34l1.06.82C6.16 17.4 9 14 17 12V8zm0-2c0 .55.45 1 1 1s1-.45 1-1V3c0-.55-.45-1-1-1s-1 .45-1 1v3z"/>
            </svg>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No Active Starter</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4">Create a starter first to view feeding history and statistics.</p>
            <a href="{{ route('starter') }}" 
               class="inline-flex items-center px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white font-medium rounded-lg transition duration-200"
               wire:navigate>
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17 8C8 10 5.9 16.17 3.82 21.34l1.06.82C6.16 17.4 9 14 17 12V8zm0-2c0 .55.45 1 1 1s1-.45 1-1V3c0-.55-.45-1-1-1s-1 .45-1 1v3z"/>
                </svg>
                Create Starter
            </a>
        </div>
    </div>
@endif