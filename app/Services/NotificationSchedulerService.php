<?php

namespace App\Services;

use App\Jobs\SendBreadProofingAlertJob;
use App\Jobs\SendFeedingReminderJob;
use App\Jobs\SendPhaseTransitionJob;
use App\Models\Starter;
use App\Models\User;
use App\Notifications\StarterHealthNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class NotificationSchedulerService
{
    public function scheduleFeedingReminders(Starter $starter): void
    {
        $user = $starter->user;
        if (!$user->telegram_chat_id) {
            Log::info('Not scheduling feeding reminders - no Telegram chat ID', [
                'starter_id' => $starter->id,
                'user_id' => $user->id
            ]);
            return;
        }

        $canFeed = $starter->canFeedNow();
        
        if ($canFeed['can_feed']) {
            // Already can feed - send immediate reminder
            SendFeedingReminderJob::dispatch($starter->id, $user->id)
                ->delay(now()->addMinutes(5)); // Small delay to avoid spam
        } elseif ($canFeed['next_feeding_time']) {
            // Schedule reminder for when feeding becomes available
            $nextFeedingTime = $canFeed['next_feeding_time'];
            
            // Schedule initial notification
            SendFeedingReminderJob::dispatch($starter->id, $user->id)
                ->delay($nextFeedingTime);
            
            // Schedule overdue notifications (1h, 3h, 6h, 12h after due time)
            $overdueIntervals = [1, 3, 6, 12];
            foreach ($overdueIntervals as $hours) {
                SendFeedingReminderJob::dispatch($starter->id, $user->id, $hours)
                    ->delay($nextFeedingTime->copy()->addHours($hours));
            }
        }

        Log::info('Scheduled feeding reminders', [
            'starter_id' => $starter->id,
            'starter_name' => $starter->name,
            'next_feeding_time' => $canFeed['next_feeding_time'] ?? 'now'
        ]);
    }

    public function scheduleBreadProofingAlerts(int $userId, array $recipe): void
    {
        $user = User::find($userId);
        if (!$user || !$user->telegram_chat_id) {
            Log::info('Not scheduling bread proofing alerts - no Telegram chat ID', [
                'user_id' => $userId
            ]);
            return;
        }

        $now = now();

        // Schedule bulk fermentation alert
        if (isset($recipe['bulk_fermentation_time'])) {
            $bulkTime = $recipe['bulk_fermentation_time']; // in minutes
            
            // 15 minutes before completion
            if ($bulkTime > 15) {
                SendBreadProofingAlertJob::dispatch($userId, 'bulk_fermentation', 15)
                    ->delay($now->copy()->addMinutes($bulkTime - 15));
            }
            
            // Completion notification
            SendBreadProofingAlertJob::dispatch($userId, 'bulk_fermentation', 0)
                ->delay($now->copy()->addMinutes($bulkTime));
        }

        // Schedule final proof alert
        if (isset($recipe['final_proof_time'])) {
            $proofTime = $recipe['final_proof_time']; // in minutes
            $startTime = $now->copy()->addMinutes($recipe['bulk_fermentation_time'] ?? 0);
            
            // 15 minutes before completion
            if ($proofTime > 15) {
                SendBreadProofingAlertJob::dispatch($userId, 'final_proof', 15)
                    ->delay($startTime->copy()->addMinutes($proofTime - 15));
            }
            
            // Completion notification
            SendBreadProofingAlertJob::dispatch($userId, 'final_proof', 0)
                ->delay($startTime->copy()->addMinutes($proofTime));
        }

        // Schedule baking completion alert
        if (isset($recipe['bake_time'])) {
            $bakeTime = $recipe['bake_time']; // in minutes
            $bakeStart = $now->copy()
                ->addMinutes($recipe['bulk_fermentation_time'] ?? 0)
                ->addMinutes($recipe['final_proof_time'] ?? 0);
            
            SendBreadProofingAlertJob::dispatch($userId, 'baking', 0)
                ->delay($bakeStart->copy()->addMinutes($bakeTime));
        }

        Log::info('Scheduled bread proofing alerts', [
            'user_id' => $userId,
            'recipe_stages' => array_keys($recipe)
        ]);
    }

    public function checkAndSchedulePhaseTransitions(Starter $starter): void
    {
        $user = $starter->user;
        if (!$user->telegram_chat_id) {
            return;
        }

        $currentPhase = $starter->getCurrentPhase();
        $day = $starter->getCurrentDay();

        // Check if transitioning from creation to maintenance phase
        if ($currentPhase === 'creation' && $day >= 7) {
            // Check if we already sent this notification
            $lastFeeding = $starter->feedings()->latest()->first();
            if ($lastFeeding && $lastFeeding->day === $day) {
                // This is the feeding that triggered the transition
                SendPhaseTransitionJob::dispatch(
                    $starter->id,
                    $user->id,
                    'creation',
                    'maintenance'
                )->delay(now()->addMinutes(2));
                
                Log::info('Scheduled phase transition notification', [
                    'starter_id' => $starter->id,
                    'from_phase' => 'creation',
                    'to_phase' => 'maintenance',
                    'day' => $day
                ]);
            }
        }
    }

    public function cancelFeedingReminders(Starter $starter): void
    {
        // Note: Laravel doesn't have a built-in way to cancel queued jobs
        // In a production environment, you might want to use a more sophisticated
        // queue system like Redis with job cancellation capabilities
        Log::info('Would cancel feeding reminders for starter', [
            'starter_id' => $starter->id,
            'starter_name' => $starter->name
        ]);
    }

    public function scheduleHealthCheckReminders(): void
    {
        // Schedule daily health check notifications for all active starters
        $starters = Starter::with('user')->get();
        
        foreach ($starters as $starter) {
            if (!$starter->user->telegram_chat_id) {
                continue;
            }

            $healthStatus = $starter->getHealthStatus();
            $lastFeeding = $starter->feedings()->latest()->first();
            
            if (!$lastFeeding) {
                continue;
            }

            $hoursSinceLastFeeding = $lastFeeding->created_at->diffInHours(now());
            $phase = $starter->getCurrentPhase();
            $maxInterval = $starter->getMaximumFeedingInterval($phase);
            
            // Only send health alerts if starter is getting unhealthy
            if ($hoursSinceLastFeeding > $maxInterval * 0.8 && in_array($healthStatus, ['fair', 'poor'])) {
                $starter->user->notify(new StarterHealthNotification(
                    $starter->name,
                    $healthStatus
                ));
            }
        }
    }
}