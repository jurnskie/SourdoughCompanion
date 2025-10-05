<?php

use App\Services\StarterService;
use Livewire\Volt\Component;
use Carbon\Carbon;

new class extends Component {
    public $notifications = [];
    public $selectedNotifications = [];
    public $filterType = '';
    public $search = '';
    public $showRescheduleModal = false;
    public $rescheduleJobId = null;
    public $newScheduleDate = '';
    public $newScheduleTime = '';
    public $showDeleteConfirm = false;
    public $deleteJobId = null;
    
    public function mount(): void
    {
        $this->loadNotifications();
    }
    
    public function loadNotifications(): void
    {
        $user = auth()->user();
        
        if ($user) {
            $starterService = app(StarterService::class);
            $allNotifications = $starterService->getUserNotifications($user);
            
            // Apply filtering
            $filtered = collect($allNotifications);
            
            if ($this->filterType) {
                $filtered = $filtered->filter(function ($notification) {
                    return $notification['type'] === $this->filterType;
                });
            }
            
            if ($this->search) {
                $filtered = $filtered->filter(function ($notification) {
                    $searchLower = strtolower($this->search);
                    $starterName = strtolower($notification['details']['starter_name'] ?? '');
                    $message = strtolower($notification['details']['message'] ?? '');
                    
                    return str_contains($starterName, $searchLower) || 
                           str_contains($message, $searchLower);
                });
            }
            
            $this->notifications = $filtered->values()->toArray();
        }
    }
    
    public function updatedFilterType(): void
    {
        $this->loadNotifications();
    }
    
    public function updatedSearch(): void
    {
        $this->loadNotifications();
    }
    
    public function toggleSelectNotification($jobId): void
    {
        if (in_array($jobId, $this->selectedNotifications)) {
            $this->selectedNotifications = array_diff($this->selectedNotifications, [$jobId]);
        } else {
            $this->selectedNotifications[] = $jobId;
        }
    }
    
    public function selectAllNotifications(): void
    {
        $this->selectedNotifications = array_column($this->notifications, 'job_id');
    }
    
    public function clearSelection(): void
    {
        $this->selectedNotifications = [];
    }
    
    public function deleteNotification($jobId): void
    {
        $this->deleteJobId = $jobId;
        $this->showDeleteConfirm = true;
    }
    
    public function confirmDelete(): void
    {
        if ($this->deleteJobId) {
            $starterService = app(StarterService::class);
            $deleted = $starterService->deleteNotification($this->deleteJobId);
            
            if ($deleted) {
                session()->flash('message', 'Notification deleted successfully!');
            } else {
                session()->flash('error', 'Failed to delete notification.');
            }
            
            $this->showDeleteConfirm = false;
            $this->deleteJobId = null;
            $this->loadNotifications();
        }
    }
    
    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = false;
        $this->deleteJobId = null;
    }
    
    public function bulkDelete(): void
    {
        if (empty($this->selectedNotifications)) {
            session()->flash('error', 'No notifications selected.');
            return;
        }
        
        $starterService = app(StarterService::class);
        $deletedCount = $starterService->deleteNotifications($this->selectedNotifications);
        
        session()->flash('message', "Deleted {$deletedCount} notifications successfully!");
        
        $this->clearSelection();
        $this->loadNotifications();
    }
    
    public function rescheduleNotification($jobId): void
    {
        $notification = collect($this->notifications)->firstWhere('job_id', $jobId);
        
        if ($notification) {
            $this->rescheduleJobId = $jobId;
            $scheduledAt = $notification['scheduled_at'];
            $this->newScheduleDate = $scheduledAt->format('Y-m-d');
            $this->newScheduleTime = $scheduledAt->format('H:i');
            $this->showRescheduleModal = true;
        }
    }
    
    public function saveReschedule(): void
    {
        $this->validate([
            'newScheduleDate' => 'required|date|after_or_equal:today',
            'newScheduleTime' => 'required|date_format:H:i',
        ]);
        
        $newScheduleTime = Carbon::createFromFormat(
            'Y-m-d H:i',
            $this->newScheduleDate . ' ' . $this->newScheduleTime
        );
        
        $starterService = app(StarterService::class);
        $updated = $starterService->updateNotificationSchedule($this->rescheduleJobId, $newScheduleTime);
        
        if ($updated) {
            session()->flash('message', 'Notification rescheduled successfully!');
        } else {
            session()->flash('error', 'Failed to reschedule notification.');
        }
        
        $this->cancelReschedule();
        $this->loadNotifications();
    }
    
    public function cancelReschedule(): void
    {
        $this->showRescheduleModal = false;
        $this->rescheduleJobId = null;
        $this->newScheduleDate = '';
        $this->newScheduleTime = '';
    }
    
    public function cleanupOrphaned(): void
    {
        $starterService = app(StarterService::class);
        $cleanedCount = $starterService->cleanupOrphanedNotifications();
        
        session()->flash('message', "Cleaned up {$cleanedCount} orphaned notifications!");
        
        $this->loadNotifications();
    }
    
    public function clearAllNotifications(): void
    {
        $user = auth()->user();
        
        if ($user) {
            $starterService = app(StarterService::class);
            $clearedCount = $starterService->clearAllNotifications($user);
            
            session()->flash('message', "Cleared {$clearedCount} scheduled notifications!");
            
            $this->loadNotifications();
        }
    }
    
    public function getNotificationTypeColor($type): string
    {
        return match($type) {
            'FeedingReminderNotification' => 'primary',
            'PhaseTransitionNotification' => 'success',
            'BreadProofingNotification' => 'warning',
            'StarterHealthNotification' => 'danger',
            'DebugTelegramNotification' => 'info',
            default => 'outline',
        };
    }
    
    public function getNotificationTypeLabel($type): string
    {
        return match($type) {
            'FeedingReminderNotification' => 'Feeding Reminder',
            'PhaseTransitionNotification' => 'Phase Transition',
            'BreadProofingNotification' => 'Bread Alert',
            'StarterHealthNotification' => 'Health Alert',
            'DebugTelegramNotification' => 'Debug',
            default => 'Unknown',
        };
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Notification Management</h1>
            <p class="text-gray-600 dark:text-gray-400">View and manage your scheduled notifications</p>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-3">
            <flux:button wire:click="cleanupOrphaned" variant="outline" size="sm"
                         wire:confirm="Clean up notifications for deleted starters?">
                <div class="flex items-center">
                    <flux:icon name="trash" class="w-4 h-4 mr-2" />
                    Cleanup Orphaned
                </div>
            </flux:button>
            
            <flux:button wire:click="clearAllNotifications" variant="danger" size="sm"
                         wire:confirm="Are you sure you want to clear ALL scheduled notifications?">
                <div class="flex items-center">
                    <flux:icon name="trash" class="w-4 h-4 mr-2" />
                    Clear All
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

    <!-- Filters and Search -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Search -->
            <flux:field>
                <flux:label>Search notifications</flux:label>
                <flux:input 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Search by starter name or message..."
                />
            </flux:field>
            
            <!-- Filter by Type -->
            <flux:field>
                <flux:label>Filter by type</flux:label>
                <flux:select wire:model.live="filterType">
                    <option value="">All Types</option>
                    <option value="FeedingReminderNotification">Feeding Reminders</option>
                    <option value="PhaseTransitionNotification">Phase Transitions</option>
                    <option value="BreadProofingNotification">Bread Alerts</option>
                    <option value="StarterHealthNotification">Health Alerts</option>
                    <option value="DebugTelegramNotification">Debug</option>
                </flux:select>
            </flux:field>
            
            <!-- Bulk Actions -->
            <flux:field>
                <flux:label>Bulk actions</flux:label>
                <div class="flex gap-2">
                    <flux:button wire:click="selectAllNotifications" variant="outline" size="sm">
                        Select All
                    </flux:button>
                    <flux:button wire:click="clearSelection" variant="outline" size="sm">
                        Clear
                    </flux:button>
                    @if (count($selectedNotifications) > 0)
                        <flux:button wire:click="bulkDelete" variant="danger" size="sm"
                                     wire:confirm="Delete {{ count($selectedNotifications) }} selected notifications?">
                            Delete ({{ count($selectedNotifications) }})
                        </flux:button>
                    @endif
                </div>
            </flux:field>
        </div>
    </div>

    <!-- Notifications List -->
    @if (count($notifications) > 0)
        <div class="space-y-4">
            @foreach ($notifications as $notification)
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-start justify-between">
                        <!-- Notification Details -->
                        <div class="flex items-start space-x-4 flex-1">
                            <!-- Checkbox -->
                            <flux:checkbox 
                                wire:click="toggleSelectNotification({{ $notification['job_id'] }})"
                                :checked="in_array($notification['job_id'], $selectedNotifications)"
                            />
                            
                            <div class="flex-1">
                                <!-- Header -->
                                <div class="flex items-center gap-3 mb-2">
                                    <flux:badge variant="{{ $this->getNotificationTypeColor($notification['type']) }}">
                                        {{ $this->getNotificationTypeLabel($notification['type']) }}
                                    </flux:badge>
                                    
                                    @if (isset($notification['details']['starter_name']))
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $notification['details']['starter_name'] }}
                                        </span>
                                    @endif
                                </div>
                                
                                <!-- Message -->
                                <p class="text-gray-700 dark:text-gray-300 mb-3">
                                    {{ $notification['details']['message'] }}
                                </p>
                                
                                <!-- Timing -->
                                <div class="text-sm text-gray-500 dark:text-gray-400 space-y-1">
                                    <div>
                                        <strong>Scheduled:</strong> 
                                        {{ $notification['scheduled_at']->format('M j, Y g:i A') }}
                                        @if ($notification['scheduled_at']->isPast())
                                            <span class="text-red-600 dark:text-red-400">(Overdue)</span>
                                        @else
                                            <span class="text-green-600 dark:text-green-400">
                                                ({{ $notification['scheduled_at']->diffForHumans() }})
                                            </span>
                                        @endif
                                    </div>
                                    <div>
                                        <strong>Created:</strong> {{ $notification['created_at']->format('M j, Y g:i A') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex gap-2">
                            <flux:button 
                                wire:click="rescheduleNotification({{ $notification['job_id'] }})" 
                                variant="outline" 
                                size="sm"
                            >
                                <flux:icon name="clock" class="w-4 h-4" />
                                <span class="sr-only">Reschedule</span>
                            </flux:button>
                            
                            <flux:button 
                                wire:click="deleteNotification({{ $notification['job_id'] }})" 
                                variant="danger" 
                                size="sm"
                            >
                                <flux:icon name="trash" class="w-4 h-4" />
                                <span class="sr-only">Delete</span>
                            </flux:button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <!-- Empty State -->
        <div class="text-center py-12">
            <div class="w-20 h-20 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-6">
                <flux:icon name="bell" class="w-10 h-10 text-gray-400 dark:text-gray-500" />
            </div>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No Notifications</h3>
            <p class="text-gray-600 dark:text-gray-400">
                @if ($search || $filterType)
                    No notifications match your current filters.
                @else
                    You don't have any scheduled notifications.
                @endif
            </p>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if ($showDeleteConfirm)
        <flux:modal wire:model.boolean="showDeleteConfirm">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Delete Notification</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Are you sure you want to delete this notification? This action cannot be undone.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-3 justify-end">
                    <flux:button wire:click="cancelDelete" variant="outline">
                        Cancel
                    </flux:button>
                    <flux:button wire:click="confirmDelete" variant="danger">
                        Delete Notification
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    <!-- Reschedule Modal -->
    @if ($showRescheduleModal)
        <flux:modal wire:model.boolean="showRescheduleModal">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Reschedule Notification</h3>
                
                <div class="space-y-4 mb-6">
                    <flux:field>
                        <flux:label>New Date</flux:label>
                        <flux:input type="date" wire:model="newScheduleDate" />
                        <flux:error name="newScheduleDate" />
                    </flux:field>
                    
                    <flux:field>
                        <flux:label>New Time</flux:label>
                        <flux:input type="time" wire:model="newScheduleTime" />
                        <flux:error name="newScheduleTime" />
                    </flux:field>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-3 justify-end">
                    <flux:button wire:click="cancelReschedule" variant="outline">
                        Cancel
                    </flux:button>
                    <flux:button wire:click="saveReschedule" variant="primary">
                        Reschedule
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>