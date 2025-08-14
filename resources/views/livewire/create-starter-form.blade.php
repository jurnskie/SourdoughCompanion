<div>
    @if(!$showForm)
        <button wire:click="showCreateForm" 
                class="bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 px-8 rounded-xl transition duration-200">
            Create Starter
        </button>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg border border-gray-200 dark:border-gray-700 w-full max-w-md">
            <form wire:submit="createStarter">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Create New Starter</h3>
                
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Starter Name
                        </label>
                        <input type="text" id="name" wire:model="name" 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                        @error('name') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                    </div>
                    
                    <div>
                        <label for="flour_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Flour Type
                        </label>
                        <select id="flour_type" wire:model="flour_type"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                            <option value="whole wheat">Whole Wheat</option>
                            <option value="white wheat">White Wheat</option>
                            <option value="rye">Rye</option>
                            <option value="spelt">Spelt</option>
                            <option value="einkorn">Einkorn</option>
                            <option value="mixed">Mixed</option>
                        </select>
                        @error('flour_type') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                    </div>
                    
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Your starter will begin in the Creation Phase, requiring daily feeding for the first 7 days.
                    </p>
                </div>
                
                <div class="flex space-x-3 mt-6">
                    <button type="button" wire:click="hideCreateForm"
                            class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-200">
                        Cancel
                    </button>
                    <button type="submit"
                            class="flex-1 bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">
                        Create
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
