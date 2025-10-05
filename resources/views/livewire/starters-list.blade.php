<?php

use App\Models\Starter;
use App\Services\StarterService;
use Livewire\Volt\Component;
use Livewire\Attributes\On;

new class extends Component {
    public $starters = [];
    public $showCreateForm = false;
    public $newStarterName = '';
    public $newStarterFlourType = 'whole wheat';
    public $editingStarter = null;
    public $showDeleteConfirm = null;
    public $deleteModalOpen = false;
    
    public function mount(): void
    {
        $this->loadStarters();
    }
    
    public function loadStarters(): void
    {
        $user = auth()->user();
        $this->starters = $user ? $user->starters()->latest()->get() : collect();
    }
    
    public function createStarter(): void
    {
        $this->validate([
            'newStarterName' => 'required|string|max:255',
            'newStarterFlourType' => 'required|string|max:255',
        ]);
        
        $starterService = app(StarterService::class);
        $starterService->createStarter($this->newStarterName, $this->newStarterFlourType);
        
        $this->reset(['newStarterName', 'newStarterFlourType', 'showCreateForm']);
        $this->loadStarters();
        
        session()->flash('message', 'Starter created successfully!');
    }
    
    public function editStarter($starterId): void
    {
        $starter = $this->starters->firstWhere('id', $starterId);
        $this->editingStarter = [
            'id' => $starter->id,
            'name' => $starter->name,
            'flour_type' => $starter->flour_type,
        ];
    }
    
    public function updateStarter(): void
    {
        $this->validate([
            'editingStarter.name' => 'required|string|max:255',
            'editingStarter.flour_type' => 'required|string|max:255',
        ]);
        
        $starter = Starter::find($this->editingStarter['id']);
        $starter->update([
            'name' => $this->editingStarter['name'],
            'flour_type' => $this->editingStarter['flour_type'],
        ]);
        
        $this->editingStarter = null;
        $this->loadStarters();
        
        session()->flash('message', 'Starter updated successfully!');
    }
    
    public function confirmDelete($starterId): void
    {
        $this->showDeleteConfirm = $starterId;
        $this->deleteModalOpen = true;
        \Log::info('Delete confirmation set', [
            'starter_id' => $starterId,
            'showDeleteConfirm' => $this->showDeleteConfirm,
            'deleteModalOpen' => $this->deleteModalOpen
        ]);
    }
    
    public function deleteStarter(): void
    {
        \Log::info('Delete starter called', [
            'showDeleteConfirm' => $this->showDeleteConfirm,
            'type' => gettype($this->showDeleteConfirm)
        ]);
        
        try {
            $starterId = $this->showDeleteConfirm;
            $starter = Starter::find($starterId);
            
            \Log::info('Starter lookup result', [
                'starter_id' => $starterId,
                'starter_found' => $starter ? 'yes' : 'no',
                'starter_name' => $starter ? $starter->name : 'N/A'
            ]);
            
            if (!$starter) {
                session()->flash('error', "Starter with ID {$starterId} not found or already deleted.");
                $this->showDeleteConfirm = null;
                $this->loadStarters();
                return;
            }
            
            $starterService = app(StarterService::class);
            $deleted = $starterService->deleteStarter($starter);
            
            \Log::info('Deletion attempt result', [
                'starter_id' => $starterId,
                'deleted' => $deleted
            ]);
            
            if ($deleted) {
                session()->flash('message', 'Starter and associated notifications deleted successfully!');
            } else {
                session()->flash('error', 'Failed to delete starter. Please try again.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting starter: ' . $e->getMessage());
            \Log::error('Starter deletion error', [
                'starter_id' => $this->showDeleteConfirm,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        $this->showDeleteConfirm = null;
        $this->deleteModalOpen = false;
        $this->loadStarters();
    }
    
    public function clearAllNotifications(): void
    {
        $user = auth()->user();
        
        if ($user) {
            $starterService = app(StarterService::class);
            $clearedCount = $starterService->clearAllNotifications($user);
            
            session()->flash('message', "Cleared {$clearedCount} scheduled notifications!");
        }
    }
    
    public function cancelEdit(): void
    {
        $this->editingStarter = null;
    }
    
    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = null;
        $this->deleteModalOpen = false;
    }
    
    public function updatedDeleteModalOpen($value): void
    {
        // If modal is closed via wire:model, ensure we reset the values
        if (!$value) {
            $this->showDeleteConfirm = null;
            $this->deleteModalOpen = false;
        }
    }

    #[On('starter-created')]
    public function refreshStarters(): void
    {
        $this->loadStarters();
    }
}; ?>

<div class="space-y-6">
    <!-- Header with Actions -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">My Starters</h2>
        <div class="flex flex-col sm:flex-row gap-3">
            <flux:button wire:click="clearAllNotifications" variant="outline" size="sm" 
                         wire:confirm="Are you sure you want to clear ALL scheduled notifications?">
                <div class="flex items-center">
                    <flux:icon name="trash" class="w-4 h-4 mr-2" />
                    Clear Notifications
                </div>
            </flux:button>
            <flux:button wire:click="$toggle('showCreateForm')" variant="primary" size="sm">
                <div class="flex items-center">
                    <flux:icon name="plus" class="w-4 h-4 mr-2" />
                    Add Starter
                </div>
            </flux:button>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('message'))
        <flux:callout variant="success" dismissible>
            {{ session('message') }}
        </flux:callout>
    @endif
    
    @if (session()->has('error'))
        <flux:callout variant="danger" dismissible>
            {{ session('error') }}
        </flux:callout>
    @endif

    <!-- Create Form -->
    @if ($showCreateForm)
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Create New Starter</h3>
            
            <div class="space-y-4">
                <flux:field>
                    <flux:label>Starter Name</flux:label>
                    <flux:input wire:model="newStarterName" placeholder="My Sourdough Starter" />
                    <flux:error name="newStarterName" />
                </flux:field>

                <flux:field>
                    <flux:label>Flour Type</flux:label>
                    <flux:select wire:model="newStarterFlourType">
                        <option value="whole wheat">Whole Wheat</option>
                        <option value="all-purpose">All-Purpose</option>
                        <option value="bread flour">Bread Flour</option>
                        <option value="rye">Rye</option>
                        <option value="spelt">Spelt</option>
                    </flux:select>
                    <flux:error name="newStarterFlourType" />
                </flux:field>

                <div class="flex flex-col sm:flex-row gap-3">
                    <flux:button wire:click="createStarter" variant="primary">
                        <div class="flex items-center">
                            <flux:icon name="plus" class="w-4 h-4 mr-2" />
                            Create Starter
                        </div>
                    </flux:button>
                    <flux:button wire:click="$toggle('showCreateForm')" variant="outline">
                        <div class="flex items-center">
                            <flux:icon name="x-mark" class="w-4 h-4 mr-2" />
                            Cancel
                        </div>
                    </flux:button>
                </div>
            </div>
        </div>
    @endif

    <!-- Starters List -->
    @if ($starters->count() > 0)
        <div class="space-y-4">
            @foreach ($starters as $starter)
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    @if ($editingStarter && $editingStarter['id'] === $starter->id)
                        <!-- Edit Form -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Edit Starter</h3>
                            
                            <flux:field>
                                <flux:label>Starter Name</flux:label>
                                <flux:input wire:model="editingStarter.name" />
                                <flux:error name="editingStarter.name" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Flour Type</flux:label>
                                <flux:select wire:model="editingStarter.flour_type">
                                    <option value="whole wheat">Whole Wheat</option>
                                    <option value="all-purpose">All-Purpose</option>
                                    <option value="bread flour">Bread Flour</option>
                                    <option value="rye">Rye</option>
                                    <option value="spelt">Spelt</option>
                                </flux:select>
                                <flux:error name="editingStarter.flour_type" />
                            </flux:field>

                            <div class="flex flex-col sm:flex-row gap-3">
                                <flux:button wire:click="updateStarter" variant="primary">
                                    <div class="flex items-center">
                                        <flux:icon name="check" class="w-4 h-4 mr-2" />
                                        Update Starter
                                    </div>
                                </flux:button>
                                <flux:button wire:click="cancelEdit" variant="outline">
                                    <div class="flex items-center">
                                        <flux:icon name="x-mark" class="w-4 h-4 mr-2" />
                                        Cancel
                                    </div>
                                </flux:button>
                            </div>
                        </div>
                    @else
                        <!-- Display Starter -->
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                        {{ $starter->name }}
                                    </h3>
                                    @php
                                        $healthStatus = $starter->getHealthStatus();
                                    @endphp
                                    <flux:badge variant="{{ $healthStatus['color'] === 'green' ? 'success' : ($healthStatus['color'] === 'orange' ? 'warning' : 'danger') }}">
                                        {{ $healthStatus['message'] }}
                                    </flux:badge>
                                </div>
                                
                                <div class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                    <div><strong>Flour Type:</strong> {{ ucfirst($starter->flour_type) }}</div>
                                    <div><strong>Phase:</strong> {{ ucfirst($starter->getCurrentPhase()) }}</div>
                                    <div><strong>Day:</strong> {{ $starter->getCurrentDay() }}</div>
                                    <div><strong>Feedings:</strong> {{ $starter->feedings->count() }}</div>
                                    <div><strong>Created:</strong> {{ $starter->created_at->format('M j, Y') }}</div>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <flux:button wire:click="editStarter({{ $starter->id }})" variant="outline" size="sm">
                                    <flux:icon name="pencil" class="w-4 h-4" />
                                    <span class="sr-only">Edit starter</span>
                                </flux:button>
                                <flux:button wire:click="confirmDelete({{ $starter->id }})" variant="danger" size="sm">
                                    <flux:icon name="trash" class="w-4 h-4" />
                                    <span class="sr-only">Delete starter</span>
                                </flux:button>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <!-- Empty State -->
        <div class="text-center py-12">
            <div class="w-20 h-20 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-6">
                <flux:icon name="beaker" class="w-10 h-10 text-gray-400 dark:text-gray-500" />
            </div>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No Starters Yet</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">Create your first sourdough starter to begin your bread making journey!</p>
            <flux:button wire:click="$toggle('showCreateForm')" variant="primary">
                <div class="flex items-center">
                    <flux:icon name="plus" class="w-4 h-4 mr-2" />
                    Create Your First Starter
                </div>
            </flux:button>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if ($showDeleteConfirm && $deleteModalOpen)
        @php
            $starterToDelete = $starters->firstWhere('id', $showDeleteConfirm);
            if (!$starterToDelete) {
                // Fallback: try to find it directly from database if not in collection
                $starterToDelete = \App\Models\Starter::find($showDeleteConfirm);
            }
        @endphp
        @if ($starterToDelete)
            <flux:modal wire:model.boolean="deleteModalOpen">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Delete Starter</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Are you sure you want to delete "{{ $starterToDelete->name }}"? This will also delete all associated feedings, photos, and cancel any scheduled notifications. This action cannot be undone.
                    </p>
                    
                    @if (config('app.debug'))
                        <div class="text-xs text-gray-500 mb-4 p-2 bg-gray-100 dark:bg-gray-700 rounded">
                            Debug: Starter ID {{ $showDeleteConfirm }}, Name: {{ $starterToDelete->name }}
                        </div>
                    @endif
                <div class="flex flex-col sm:flex-row gap-3 justify-end">
                    <flux:button wire:click="cancelDelete" variant="outline">
                        <div class="flex items-center">
                            <flux:icon name="x-mark" class="w-4 h-4 mr-2" />
                            Cancel
                        </div>
                    </flux:button>
                    <flux:button wire:click="deleteStarter" variant="danger">
                        <div class="flex items-center">
                            <flux:icon name="trash" class="w-4 h-4 mr-2" />
                            Delete Starter
                        </div>
                    </flux:button>
                </div>
                </div>
            </flux:modal>
        @endif
    @endif
</div>