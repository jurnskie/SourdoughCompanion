<?php

namespace App\Services;

use App\Models\BakingTimer;
use App\Models\User;
use App\Services\NotificationSchedulerService;
use Illuminate\Support\Facades\Log;

class BakingTimerService
{
    public function __construct(
        private NotificationSchedulerService $notificationScheduler
    ) {}

    /**
     * Start a new baking timer
     */
    public function startTimer(int $userId, array $recipe): BakingTimer
    {
        $user = User::find($userId);
        if (!$user) {
            throw new \InvalidArgumentException("User not found: {$userId}");
        }

        if (!$user->telegram_chat_id) {
            throw new \InvalidArgumentException("User must have a Telegram chat ID to receive notifications");
        }

        // Cancel any existing active timers for this user
        $this->cancelActiveTimers($userId);

        // Calculate total duration
        $bulkTime = $recipe['bulk_fermentation_time'] ?? 0;
        $proofTime = $recipe['final_proof_time'] ?? 0;
        $bakeTime = $recipe['bake_time'] ?? 45;
        $totalMinutes = $bulkTime + $proofTime + $bakeTime;

        // Create new timer
        $timer = BakingTimer::create([
            'user_id' => $userId,
            'recipe_data' => $recipe,
            'start_time' => now(),
            'total_duration_minutes' => $totalMinutes,
            'current_stage' => 'bulk_fermentation',
            'status' => 'active',
        ]);

        // Schedule notifications
        $this->notificationScheduler->scheduleBreadProofingAlerts($userId, $recipe);

        Log::info('Baking timer started', [
            'timer_id' => $timer->id,
            'user_id' => $userId,
            'total_duration_minutes' => $totalMinutes,
            'recipe_stages' => array_keys($recipe)
        ]);

        return $timer;
    }

    /**
     * Cancel active timers for a user
     */
    public function cancelActiveTimers(int $userId): int
    {
        $cancelledCount = BakingTimer::forUser($userId)
            ->active()
            ->update([
                'status' => 'cancelled',
                'completed_at' => now(),
            ]);

        if ($cancelledCount > 0) {
            // Also cancel any queued notifications for this user
            $this->notificationScheduler->cancelBreadProofingAlerts($userId);
            
            Log::info('Cancelled active baking timers', [
                'user_id' => $userId,
                'cancelled_count' => $cancelledCount
            ]);
        }

        return $cancelledCount;
    }

    /**
     * Get active timer for user
     */
    public function getActiveTimer(int $userId): ?BakingTimer
    {
        return BakingTimer::forUser($userId)->active()->latest()->first();
    }

    /**
     * Check if user has an active timer
     */
    public function hasActiveTimer(int $userId): bool
    {
        return BakingTimer::forUser($userId)->active()->exists();
    }

    /**
     * Cancel a specific timer
     */
    public function cancelTimer(int $timerId): bool
    {
        $timer = BakingTimer::find($timerId);
        if (!$timer || !$timer->isActive()) {
            return false;
        }

        $timer->cancel();

        // Cancel any queued notifications for this user
        $this->notificationScheduler->cancelBreadProofingAlerts($timer->user_id);

        Log::info('Baking timer cancelled', [
            'timer_id' => $timerId,
            'user_id' => $timer->user_id
        ]);

        return true;
    }

    /**
     * Complete a timer manually
     */
    public function completeTimer(int $timerId): bool
    {
        $timer = BakingTimer::find($timerId);
        if (!$timer || !$timer->isActive()) {
            return false;
        }

        $timer->markCompleted();

        Log::info('Baking timer completed manually', [
            'timer_id' => $timerId,
            'user_id' => $timer->user_id,
            'elapsed_minutes' => $timer->getElapsedMinutes()
        ]);

        return true;
    }

    /**
     * Get timer statistics for user
     */
    public function getTimerStats(int $userId): array
    {
        $timers = BakingTimer::forUser($userId)->get();
        
        return [
            'total_timers' => $timers->count(),
            'active_timers' => $timers->where('status', 'active')->count(),
            'completed_timers' => $timers->where('status', 'completed')->count(),
            'cancelled_timers' => $timers->where('status', 'cancelled')->count(),
            'total_baking_hours' => round($timers->sum('total_duration_minutes') / 60, 1),
        ];
    }

    /**
     * Clean up old completed/cancelled timers
     */
    public function cleanupOldTimers(int $daysOld = 30): int
    {
        $deletedCount = BakingTimer::whereIn('status', ['completed', 'cancelled'])
            ->where('completed_at', '<', now()->subDays($daysOld))
            ->delete();

        if ($deletedCount > 0) {
            Log::info('Cleaned up old baking timers', [
                'deleted_count' => $deletedCount,
                'days_old' => $daysOld
            ]);
        }

        return $deletedCount;
    }
}