<?php

namespace App\Console\Commands;

use App\Jobs\SendFeedingReminderJob;
use App\Models\Starter;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CheckFeedingReminders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'starter:check-feeding-reminders';

    /**
     * The console command description.
     */
    protected $description = 'Check all starters and send feeding reminders when needed';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking feeding reminders for all starters...');

        $starters = Starter::with(['user', 'feedings' => function ($query) {
            $query->latest()->limit(1);
        }])->get();

        $remindersChecked = 0;
        $remindersSent = 0;

        foreach ($starters as $starter) {
            $remindersChecked++;

            if (!$this->shouldCheckStarter($starter)) {
                continue;
            }

            $reminderSent = $this->checkAndSendFeedingReminder($starter);
            if ($reminderSent) {
                $remindersSent++;
            }
        }

        $this->info("Checked {$remindersChecked} starters, sent {$remindersSent} feeding reminders");

        Log::info('Feeding reminder check completed', [
            'starters_checked' => $remindersChecked,
            'reminders_sent' => $remindersSent
        ]);

        return self::SUCCESS;
    }

    /**
     * Check if we should evaluate this starter for feeding reminders
     */
    private function shouldCheckStarter(Starter $starter): bool
    {
        // Skip if user has no Telegram chat ID
        if (!$starter->user->telegram_chat_id) {
            return false;
        }

        // Skip if no feedings yet
        if ($starter->feedings->isEmpty()) {
            return false;
        }

        return true;
    }

    /**
     * Check if starter needs feeding and send reminder if appropriate
     */
    private function checkAndSendFeedingReminder(Starter $starter): bool
    {
        $lastFeeding = $starter->feedings->first();
        $now = now();
        
        // Get feeding status
        $canFeed = $starter->canFeedNow();
        
        if (!$canFeed['can_feed']) {
            // Not time to feed yet
            return false;
        }

        // Calculate hours since minimum feeding interval passed
        $phase = $starter->getCurrentPhase();
        $minimumHours = $starter->getMinimumFeedingInterval($phase);
        $minimumFeedingTime = $lastFeeding->created_at->copy()->addHours($minimumHours);
        
        $hoursOverdue = 0;
        if ($now->gt($minimumFeedingTime)) {
            $hoursOverdue = $minimumFeedingTime->diffInHours($now);
        }

        // Check if we recently sent a reminder (avoid spam)
        $cacheKey = "feeding_reminder_sent_{$starter->id}";
        $lastReminderSent = Cache::get($cacheKey);
        
        if ($lastReminderSent) {
            $lastReminderTime = Carbon::parse($lastReminderSent);
            $hoursSinceLastReminder = $lastReminderTime->diffInHours($now);
            
            // Don't send reminders more than once every 2 hours
            if ($hoursSinceLastReminder < 2) {
                return false;
            }
            
            // For overdue starters, use escalating intervals
            if ($hoursOverdue > 0) {
                $requiredInterval = $this->getOverdueReminderInterval($hoursOverdue);
                if ($hoursSinceLastReminder < $requiredInterval) {
                    return false;
                }
            }
        }

        // Send the reminder via queued job (consistent with other notifications)
        SendFeedingReminderJob::dispatch(
            $starter->id,
            $starter->user_id,
            $hoursOverdue
        );

        // Cache that we sent a reminder
        Cache::put($cacheKey, $now->toISOString(), now()->addHours(24));

        $this->line("📧 Sent feeding reminder for '{$starter->name}' (overdue: {$hoursOverdue}h)");

        Log::info('Feeding reminder sent', [
            'starter_id' => $starter->id,
            'starter_name' => $starter->name,
            'user_id' => $starter->user_id,
            'hours_overdue' => $hoursOverdue,
            'last_feeding' => $lastFeeding->created_at->toISOString()
        ]);

        return true;
    }

    /**
     * Get the required interval between reminders based on how overdue the feeding is
     */
    private function getOverdueReminderInterval(int $hoursOverdue): int
    {
        return match (true) {
            $hoursOverdue >= 12 => 2, // Every 2 hours for very overdue
            $hoursOverdue >= 6 => 3,  // Every 3 hours for moderately overdue  
            $hoursOverdue >= 2 => 4,  // Every 4 hours for slightly overdue
            default => 6,             // Every 6 hours for just due
        };
    }
}