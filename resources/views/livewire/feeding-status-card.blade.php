@php
    $canFeedResult = $starter->canFeedNow();
@endphp

<div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Feeding Status</h3>
    
    @if($canFeedResult['can_feed'])
        <div class="flex items-start space-x-3 mb-4">
            <svg class="w-6 h-6 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <div class="flex-1">
                <h4 class="font-medium text-gray-900 dark:text-white">Ready to Feed</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Recommended ratio: {{ $starter->getRecommendedRatio() }}
                </p>
            </div>
        </div>
    @else
        <div class="flex items-start space-x-3 mb-4">
            <svg class="w-6 h-6 text-orange-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
            </svg>
            <div class="flex-1">
                <h4 class="font-medium text-gray-900 dark:text-white">Too Soon</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $canFeedResult['reason'] }}
                </p>
            </div>
        </div>
    @endif
    
    <!-- Feed Button -->
    <button wire:click="showFeedingModal" 
            @disabled(!$canFeedResult['can_feed'])
            class="w-full flex items-center justify-center space-x-2 py-3 px-4 rounded-lg font-medium transition duration-200
                   {{ $canFeedResult['can_feed'] 
                      ? 'bg-orange-500 hover:bg-orange-600 text-white' 
                      : 'bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 cursor-not-allowed' }}">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12,3.77L11.25,4.61C11.25,4.61 9.97,6.06 8.68,7.94C7.39,9.82 6,12.07 6,14.23A6,6 0 0,0 12,20.23A6,6 0 0,0 18,14.23C18,12.07 16.61,9.82 15.32,7.94C14.03,6.06 12.75,4.61 12.75,4.61L12,3.77Z"/>
        </svg>
        <span>Feed Starter</span>
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
        </svg>
    </button>
    
    <!-- Feeding Form Modal -->
    @if($showFeedingForm)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-md shadow-xl">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Feed Starter</h3>
                
                <form wire:submit="feedStarter">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Starter to keep (grams)
                            </label>
                            <input type="number" wire:model="starterAmount" min="5" max="100" step="5"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Flour to add (grams)
                            </label>
                            <input type="number" wire:model="flourAmount" min="10" max="500" step="10"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Water to add (grams)
                            </label>
                            <input type="number" wire:model="waterAmount" min="10" max="500" step="10"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        
                        <!-- Photo Upload Section -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                📸 Progress Photo (optional)
                            </label>
                            
                            @if($photo)
                                <div class="relative mb-3">
                                    <img src="{{ $photo->temporaryUrl() }}" alt="Preview" class="w-full h-32 object-cover rounded-lg">
                                    <button type="button" wire:click="removePhoto" 
                                            class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 hover:bg-red-600">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                </div>
                            @else
                                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center">
                                    <input type="file" wire:model="photo" accept="image/*" capture="environment" class="hidden" id="photo-upload">
                                    <label for="photo-upload" class="cursor-pointer">
                                        <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                                        </svg>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Tap to take photo</p>
                                    </label>
                                </div>
                            @endif
                            
                            @error('photo')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total feeding:</p>
                            <p class="font-medium text-gray-900 dark:text-white">
                                {{ $starterAmount }}g + {{ $flourAmount }}g + {{ $waterAmount }}g = {{ $starterAmount + $flourAmount + $waterAmount }}g
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Ratio: {{ $ratio }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex space-x-3 mt-6">
                        <button type="button" wire:click="hideFeedingModal"
                                class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                            Cancel
                        </button>
                        <button type="submit"
                                class="flex-1 bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2 px-4 rounded-lg">
                            Feed
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
