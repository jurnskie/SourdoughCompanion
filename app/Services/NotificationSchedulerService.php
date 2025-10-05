<?php

namespace App\Services;

use App\Jobs\SendBreadProofingAlertJob;
use App\Jobs\SendFeedingReminderJob;
use App\Jobs\SendPhaseTransitionJob;
use App\Models\Starter;
use App\Models\User;
use App\Notifications\StarterHealthNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationSchedulerService
{
    public function scheduleFeedingReminders(Starter $starter): void
    {
        $user = $starter->user;
        if (! $user->telegram_chat_id) {
            Log::info('Not scheduling feeding reminders - no Telegram chat ID', [
                'starter_id' => $starter->id,
                'user_id' => $user->id,
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
            'next_feeding_time' => $canFeed['next_feeding_time'] ?? 'now',
        ]);
    }

    public function scheduleBreadProofingAlerts(int $userId, array $recipe): void
    {
        $user = User::find($userId);
        if (! $user || ! $user->telegram_chat_id) {
            Log::info('Not scheduling bread proofing alerts - no Telegram chat ID', [
                'user_id' => $userId,
            ]);

            return;
        }

        // Cancel any existing bread proofing alerts for this user to prevent duplicates
        $this->cancelBreadProofingAlerts($userId);

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
            'recipe_stages' => array_keys($recipe),
        ]);
    }

    public function checkAndSchedulePhaseTransitions(Starter $starter): void
    {
        $user = $starter->user;
        if (! $user->telegram_chat_id) {
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
                    'day' => $day,
                ]);
            }
        }
    }

    public function cancelFeedingReminders(Starter $starter): int
    {
        // Remove all queued SendFeedingReminderJob jobs for this starter
        $deleted = DB::table('jobs')
            ->where('payload', 'like', '%SendFeedingReminderJob%')
            ->where('payload', 'like', "%\"starterId\":{$starter->id}%")
            ->delete();

        if ($deleted > 0) {
            Log::info('Cancelled feeding reminders for starter', [
                'starter_id' => $starter->id,
                'starter_name' => $starter->name,
                'cancelled_jobs' => $deleted,
            ]);
        }

        return $deleted;
    }

    public function scheduleHealthCheckReminders(): void
    {
        // Schedule daily health check notifications for all active starters
        $starters = Starter::with('user')->get();

        foreach ($starters as $starter) {
            if (! $starter->user->telegram_chat_id) {
                continue;
            }

            $healthStatus = $starter->getHealthStatus();
            $lastFeeding = $starter->feedings()->latest()->first();

            if (! $lastFeeding) {
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

    /**
     * Cancel all existing bread proofing alerts for a user
     */
    public function cancelBreadProofingAlerts(int $userId): int
    {
        // Remove all queued SendBreadProofingAlertJob jobs for this user
        $deleted = DB::table('jobs')
            ->where('payload', 'like', '%SendBreadProofingAlertJob%')
            ->where('payload', 'like', "%\"userId\":{$userId}%")
            ->delete();

        if ($deleted > 0) {
            Log::info('Cancelled existing bread proofing alerts', [
                'user_id' => $userId,
                'cancelled_jobs' => $deleted,
            ]);
        }

        return $deleted;
    }

    /**
     * Clear all notification schedules for a user (all starter notifications)
     */
    public function clearAllUserNotifications(int $userId): int
    {
        $totalDeleted = 0;

        // Cancel all feeding reminder jobs for this user
        $feedingJobs = DB::table('jobs')
            ->where('payload', 'like', '%SendFeedingReminderJob%')
            ->where('payload', 'like', "%\"userId\":{$userId}%")
            ->delete();

        $totalDeleted += $feedingJobs;

        // Cancel all phase transition jobs for this user
        $phaseJobs = DB::table('jobs')
            ->where('payload', 'like', '%SendPhaseTransitionJob%')
            ->where('payload', 'like', "%\"userId\":{$userId}%")
            ->delete();

        $totalDeleted += $phaseJobs;

        // Cancel all bread proofing alert jobs for this user
        $breadJobs = $this->cancelBreadProofingAlerts($userId);
        $totalDeleted += $breadJobs;

        if ($totalDeleted > 0) {
            Log::info('Cleared all notification schedules for user', [
                'user_id' => $userId,
                'total_cancelled_jobs' => $totalDeleted,
                'feeding_jobs' => $feedingJobs,
                'phase_jobs' => $phaseJobs,
                'bread_jobs' => $breadJobs,
            ]);
        }

        return $totalDeleted;
    }

    /**
     * Get all scheduled notifications for a user with parsed details
     */
    public function getUserNotifications(int $userId): array
    {
        $jobs = DB::table('jobs')
            ->orderBy('available_at', 'asc')
            ->get();

        $notifications = [];

        foreach ($jobs as $job) {
            $payload = json_decode($job->payload, true);

            // Skip if can't parse payload
            if (! isset($payload['data']['command'])) {
                continue;
            }

            $command = $payload['data']['command'];
            $notification = $this->parseNotificationFromCommand($command, $userId);

            if ($notification) {
                $notification['job_id'] = $job->id;
                $notification['scheduled_at'] = \Carbon\Carbon::createFromTimestamp($job->available_at);
                $notification['created_at'] = \Carbon\Carbon::createFromTimestamp($job->created_at);
                $notifications[] = $notification;
            }
        }

        return $notifications;
    }

    /**
     * Parse notification details from job command
     */
    private function parseNotificationFromCommand(string $command, int $targetUserId): ?array
    {
        // Unserialize the command to get notification details
        try {
            $commandObj = unserialize($command);

            if (! isset($commandObj->notification)) {
                return null;
            }

            $notification = $commandObj->notification;
            $notifiables = $commandObj->notifiables;

            // Check if this notification is for the target user
            $isForUser = false;
            if (isset($notifiables->id)) {
                if (is_array($notifiables->id)) {
                    $isForUser = in_array($targetUserId, $notifiables->id);
                } else {
                    $isForUser = $notifiables->id == $targetUserId;
                }
            }

            if (! $isForUser) {
                return null;
            }

            // Parse notification details based on type
            $type = class_basename(get_class($notification));
            $details = $this->getNotificationDetails($notification, $type);

            return [
                'type' => $type,
                'details' => $details,
                'notification_id' => $notification->id ?? null,
            ];

        } catch (\Exception $e) {
            Log::warning('Failed to parse notification command', [
                'error' => $e->getMessage(),
                'command_start' => substr($command, 0, 100),
            ]);

            return null;
        }
    }

    /**
     * Get human-readable details for different notification types
     */
    private function getNotificationDetails($notification, string $type): array
    {
        switch ($type) {
            case 'FeedingReminderNotification':
                return [
                    'starter_name' => $notification->starterName ?? 'Unknown Starter',
                    'hours_overdue' => $notification->hoursOverdue ?? 0,
                    'message' => $this->buildFeedingReminderMessage($notification),
                ];

            case 'PhaseTransitionNotification':
                return [
                    'starter_name' => $notification->starterName ?? 'Unknown Starter',
                    'from_phase' => $notification->fromPhase ?? 'Unknown',
                    'to_phase' => $notification->toPhase ?? 'Unknown',
                    'message' => $this->buildPhaseTransitionMessage($notification),
                ];

            case 'BreadProofingNotification':
                return [
                    'stage' => $notification->stage ?? 'Unknown',
                    'minutes_remaining' => $notification->minutesRemaining ?? 0,
                    'message' => $this->buildBreadProofingMessage($notification),
                ];

            case 'StarterHealthNotification':
                return [
                    'starter_name' => $notification->starterName ?? 'Unknown Starter',
                    'health_status' => $notification->healthStatus ?? 'Unknown',
                    'message' => $this->buildHealthMessage($notification),
                ];

            case 'DebugTelegramNotification':
                return [
                    'message' => $notification->message ?? 'Debug notification',
                    'use_fallback' => $notification->useFallback ?? false,
                ];

            default:
                return [
                    'message' => 'Unknown notification type',
                ];
        }
    }

    /**
     * Build human-readable message for feeding reminder
     */
    private function buildFeedingReminderMessage($notification): string
    {
        $starterName = $notification->starterName ?? 'your starter';
        $hoursOverdue = $notification->hoursOverdue ?? 0;

        if ($hoursOverdue > 0) {
            return "Feeding reminder: {$starterName} is {$hoursOverdue} hours overdue for feeding";
        }

        return "Feeding reminder: Time to feed {$starterName}";
    }

    /**
     * Build human-readable message for phase transition
     */
    private function buildPhaseTransitionMessage($notification): string
    {
        $starterName = $notification->starterName ?? 'your starter';
        $fromPhase = $notification->fromPhase ?? 'unknown';
        $toPhase = $notification->toPhase ?? 'unknown';

        return "Phase transition: {$starterName} has moved from {$fromPhase} to {$toPhase} phase";
    }

    /**
     * Build human-readable message for bread proofing
     */
    private function buildBreadProofingMessage($notification): string
    {
        $stage = $notification->stage ?? 'unknown';
        $minutesRemaining = $notification->minutesRemaining ?? 0;

        if ($minutesRemaining > 0) {
            return "Bread alert: {$stage} stage - {$minutesRemaining} minutes remaining";
        }

        return "Bread alert: {$stage} stage completed";
    }

    /**
     * Build human-readable message for health notification
     */
    private function buildHealthMessage($notification): string
    {
        $starterName = $notification->starterName ?? 'your starter';
        $healthStatus = $notification->healthStatus ?? 'unknown';

        return "Health alert: {$starterName} status is {$healthStatus}";
    }

    /**
     * Delete a specific notification job
     */
    public function deleteNotification(int $jobId): bool
    {
        $deleted = DB::table('jobs')
            ->where('id', $jobId)
            ->delete();

        if ($deleted > 0) {
            Log::info('Deleted specific notification', [
                'job_id' => $jobId,
            ]);
        }

        return $deleted > 0;
    }

    /**
     * Delete multiple notifications by job IDs
     */
    public function deleteNotifications(array $jobIds): int
    {
        if (empty($jobIds)) {
            return 0;
        }

        $deleted = DB::table('jobs')
            ->whereIn('id', $jobIds)
            ->delete();

        if ($deleted > 0) {
            Log::info('Bulk deleted notifications', [
                'job_ids' => $jobIds,
                'deleted_count' => $deleted,
            ]);
        }

        return $deleted;
    }

    /**
     * Update notification schedule time
     */
    public function updateNotificationSchedule(int $jobId, \Carbon\Carbon $newScheduleTime): bool
    {
        $updated = DB::table('jobs')
            ->where('id', $jobId)
            ->update([
                'available_at' => $newScheduleTime->timestamp,
            ]);

        if ($updated > 0) {
            Log::info('Updated notification schedule', [
                'job_id' => $jobId,
                'new_schedule_time' => $newScheduleTime->toISOString(),
            ]);
        }

        return $updated > 0;
    }

    /**
     * Clean up orphaned notifications for deleted starters
     */
    public function cleanupOrphanedNotifications(): int
    {
        // Get all existing starter names
        $existingStarters = \App\Models\Starter::pluck('name')->toArray();

        $totalDeleted = 0;
        $jobs = DB::table('jobs')->get();

        foreach ($jobs as $job) {
            $payload = json_decode($job->payload, true);

            if (! isset($payload['data']['command'])) {
                continue;
            }

            try {
                $command = unserialize($payload['data']['command']);

                if (! isset($command->notification)) {
                    continue;
                }

                $notification = $command->notification;

                // Check if this notification references a starter that no longer exists
                if (isset($notification->starterName)) {
                    $starterName = $notification->starterName;

                    if (! in_array($starterName, $existingStarters)) {
                        // This notification is for a deleted starter
                        DB::table('jobs')->where('id', $job->id)->delete();
                        $totalDeleted++;

                        Log::info('Cleaned up orphaned notification', [
                            'job_id' => $job->id,
                            'starter_name' => $starterName,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                // Skip malformed jobs
                continue;
            }
        }

        return $totalDeleted;
    }
}
