<div class="space-y-6">
    <!-- Input Controls -->
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recipe Parameters</h3>
        
        <div class="space-y-4">
            <!-- Flour Weight -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Flour Weight (grams)
                </label>
                <input type="number" wire:model.live="flourWeight" min="100" max="2000" step="50"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500">
                @error('flourWeight') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>
            
            <!-- Loaves and Recipe Type -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Number of Loaves
                    </label>
                    <select wire:model.live="loaves" 
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500">
                        @for($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}">{{ $i }} loaf{{ $i > 1 ? 'es' : '' }}</option>
                        @endfor
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Recipe Type
                    </label>
                    <select wire:model.live="recipeType"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500">
                        <option value="basic">Basic</option>
                        <option value="whole-grain">Whole Grain</option>
                        <option value="high-hydration">High Hydration</option>
                    </select>
                </div>
            </div>
            
            <!-- Weather Toggle -->
            <div class="flex items-center space-x-3">
                <input type="checkbox" wire:model.live="useWeather" id="useWeather"
                       class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                <label for="useWeather" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Use current weather conditions
                </label>
            </div>
            
            <!-- Weather Input -->
            @if($useWeather)
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Location (optional)
                    </label>
                    <input type="text" wire:model="location" placeholder="Leave empty for auto-detection"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500">
                </div>
            @else
                <!-- Manual Environment -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Temperature (°C)
                        </label>
                        <input type="number" wire:model.live="manualTemperature" min="5" max="40"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Humidity
                        </label>
                        <select wire:model.live="manualHumidity"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500">
                            <option value="dry">Dry</option>
                            <option value="normal">Normal</option>
                            <option value="humid">Humid</option>
                        </select>
                    </div>
                </div>
            @endif
        </div>
    </div>
    
    <!-- Weather Display -->
    @if($useWeather && $weather)
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
            <div class="flex items-center space-x-2 mb-2">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                </svg>
                <h4 class="font-medium text-blue-900 dark:text-blue-100">Current Conditions</h4>
            </div>
            <div class="text-sm text-blue-800 dark:text-blue-200">
                <p><strong>Location:</strong> {{ $weather['location'] ?? 'Unknown' }}</p>
                <p><strong>Temperature:</strong> {{ $weather['temperature'] }}°C</p>
                <p><strong>Humidity:</strong> {{ $weather['humidity'] }}% ({{ $recipe['humidity_level'] ?? 'normal' }})</p>
                <p><strong>Source:</strong> {{ $weather['source'] }}</p>
            </div>
        </div>
    @endif
    
    <!-- Recipe Results -->
    @if($recipe)
        <div class="space-y-4">
            <!-- Ingredients -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Ingredients</h3>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="font-medium text-gray-900 dark:text-white">Flour</span>
                            <span class="text-gray-600 dark:text-gray-400">{{ $recipe['ingredients']['flour'] }}g</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="font-medium text-gray-900 dark:text-white">Water</span>
                            <span class="text-gray-600 dark:text-gray-400">{{ $recipe['ingredients']['water'] }}g</span>
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="font-medium text-gray-900 dark:text-white">Starter</span>
                            <span class="text-gray-600 dark:text-gray-400">{{ $recipe['ingredients']['starter'] }}g</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="font-medium text-gray-900 dark:text-white">Salt</span>
                            <span class="text-gray-600 dark:text-gray-400">{{ $recipe['ingredients']['salt'] }}g</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Percentages and Timing -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Percentages -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Percentages</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Hydration</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ round($recipe['percentages']['hydration'], 1) }}%</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Starter</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ round($recipe['percentages']['starter'], 1) }}%</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Salt</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $recipe['percentages']['salt'] }}%</span>
                        </div>
                    </div>
                </div>
                
                <!-- Timing -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Timing</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Bulk Fermentation</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $recipe['timing']['bulk_fermentation_hours'] }}h</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Final Proof</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $recipe['timing']['final_proof_hours'] }}h</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Total Time</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $recipe['timing']['total_time_hours'] }}h</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Baking Timer -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Baking Timer</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Get Telegram notifications at each baking stage</p>
                    </div>
                    
                    @if($activeTimer)
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2 text-green-600 dark:text-green-400">
                                    <svg class="w-5 h-5 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="font-medium">Timer Active - {{ $activeTimer->getCurrentStageInfo()['name'] }}</span>
                                </div>
                                <button 
                                    wire:click="cancelBakingTimer"
                                    class="text-xs text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                    Cancel
                                </button>
                            </div>
                            
                            <!-- Progress Bar -->
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full" style="width: {{ $activeTimer->getProgress() }}%"></div>
                            </div>
                            
                            <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
                                <span>{{ $activeTimer->getElapsedMinutes() }}min elapsed</span>
                                <span>{{ $activeTimer->getRemainingMinutes() }}min remaining</span>
                            </div>
                        </div>
                    @else
                        <button 
                            wire:click="startBakingTimer"
                            class="inline-flex items-center px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white font-medium rounded-lg transition duration-200">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22C6.47,22 2,17.5 2,12A10,10 0 0,1 12,2M12.5,7V12.25L17,14.92L16.25,16.15L11,13V7H12.5Z"/>
                            </svg>
                            Start Timer
                        </button>
                    @endif
                </div>
                
                @if($activeTimer)
                    <div class="mt-4 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                        <p class="text-sm text-green-800 dark:text-green-200">
                            🍞 You'll receive Telegram messages when each stage is complete:
                        </p>
                        <ul class="mt-2 text-xs text-green-700 dark:text-green-300 space-y-1">
                            <li>• Bulk fermentation ({{ $recipe['timing']['bulk_fermentation_hours'] }}h)</li>
                            <li>• Final proof ({{ $recipe['timing']['final_proof_hours'] }}h)</li>
                            <li>• Baking complete (45min)</li>
                        </ul>
                    </div>
                @endif
            </div>

            <!-- Environment Adjustments -->
            @if(isset($recipe['adjustments']) && ($recipe['adjustments']['hydration_adjustment'] != 0 || $recipe['adjustments']['timing_adjustment'] != 0))
                <div class="bg-orange-50 dark:bg-orange-900/20 rounded-xl p-4 border border-orange-200 dark:border-orange-800">
                    <h4 class="font-medium text-orange-900 dark:text-orange-100 mb-2">Environmental Adjustments</h4>
                    <div class="text-sm text-orange-800 dark:text-orange-200 space-y-1">
                        @if($recipe['adjustments']['hydration_adjustment'] != 0)
                            <p>Hydration {{ $recipe['adjustments']['hydration_adjustment'] > 0 ? 'increased' : 'decreased' }} by {{ abs($recipe['adjustments']['hydration_adjustment']) }}% for {{ $recipe['humidity_level'] }} conditions</p>
                        @endif
                        @if($recipe['adjustments']['timing_adjustment'] != 0)
                            <p>Bulk fermentation {{ $recipe['adjustments']['timing_adjustment'] > 0 ? 'extended' : 'reduced' }} by {{ abs($recipe['adjustments']['timing_adjustment']) }}h for {{ $recipe['humidity_level'] }} conditions</p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @elseif($isCalculating)
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-center space-x-2">
                <svg class="animate-spin w-5 h-5 text-orange-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-gray-600 dark:text-gray-400">Calculating recipe...</span>
            </div>
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="text-center text-gray-500 dark:text-gray-400">
                <p>Create a starter first to calculate recipes!</p>
                <a href="{{ route('starter') }}" 
                   class="inline-block mt-2 text-orange-600 dark:text-orange-400 hover:text-orange-700 dark:hover:text-orange-300 font-medium"
                   wire:navigate>
                    Go to Starter →
                </a>
            </div>
        </div>
    @endif
</div>
