<?php

namespace App\Console\Commands;

use App\Models\BakingTimer;
use App\Services\NotificationSchedulerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckBakingTimers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'baking:check-timers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check active baking timers and ensure notifications are properly scheduled';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking active baking timers...');

        $activeTimers = BakingTimer::active()->get();
        
        if ($activeTimers->isEmpty()) {
            $this->info('No active baking timers found.');
            return self::SUCCESS;
        }

        $this->info("Found {$activeTimers->count()} active timer(s)");
        
        $completedCount = 0;
        $rescheduledCount = 0;
        $notificationScheduler = app(NotificationSchedulerService::class);

        foreach ($activeTimers as $timer) {
            $elapsed = $timer->getElapsedMinutes();
            $stage = $timer->getCurrentStageInfo();
            
            $this->info("Timer #{$timer->id}: {$elapsed}min elapsed, current stage: {$stage['name']}");

            // Check if timer should be completed
            if ($timer->getRemainingMinutes() <= 0) {
                $timer->markCompleted();
                $completedCount++;
                
                Log::info('Baking timer completed', [
                    'timer_id' => $timer->id,
                    'user_id' => $timer->user_id,
                    'total_duration' => $timer->total_duration_minutes
                ]);
                
                $this->line("  → Timer completed");
                continue;
            }

            // Verify notifications are scheduled for remaining stages
            try {
                $this->verifyNotificationsScheduled($timer, $notificationScheduler);
                $rescheduledCount++;
                $this->line("  → Notifications verified/rescheduled");
            } catch (\Exception $e) {
                $this->error("  → Failed to verify notifications: " . $e->getMessage());
                
                Log::error('Failed to verify baking timer notifications', [
                    'timer_id' => $timer->id,
                    'user_id' => $timer->user_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->info("\nSummary:");
        $this->line("- Completed timers: {$completedCount}");
        $this->line("- Verified/rescheduled: {$rescheduledCount}");
        
        Log::info('Baking timer check completed', [
            'active_timers' => $activeTimers->count(),
            'completed' => $completedCount,
            'rescheduled' => $rescheduledCount
        ]);

        return self::SUCCESS;
    }

    private function verifyNotificationsScheduled(BakingTimer $timer, NotificationSchedulerService $scheduler): void
    {
        // Check if the timer's current stage has changed since last check
        $currentStage = $timer->getCurrentStageInfo();
        $storedStage = $timer->current_stage;
        
        // Only reschedule if the stage has actually changed
        if ($currentStage['stage'] === $storedStage) {
            $this->line("  → Stage unchanged ({$currentStage['stage']}), skipping reschedule");
            return;
        }
        
        // Update the stored stage
        $timer->update(['current_stage' => $currentStage['stage']]);
        
        $this->line("  → Stage changed from {$storedStage} to {$currentStage['stage']}, rescheduling notifications");
        
        $recipe = $timer->recipe_data;
        $elapsed = $timer->getElapsedMinutes();
        
        $bulkTime = $recipe['bulk_fermentation_time'] ?? 0;
        $proofTime = $recipe['final_proof_time'] ?? 0;
        $bakeTime = $recipe['bake_time'] ?? 45;
        
        // Only reschedule notifications for future stages
        $modifiedRecipe = [];
        
        // Bulk fermentation
        if ($elapsed < $bulkTime) {
            $modifiedRecipe['bulk_fermentation_time'] = $bulkTime - $elapsed;
        }
        
        // Final proof
        if ($elapsed < $bulkTime + $proofTime) {
            $modifiedRecipe['final_proof_time'] = $proofTime;
            // Adjust start time for final proof
            if ($elapsed < $bulkTime) {
                $modifiedRecipe['bulk_fermentation_time'] = $bulkTime - $elapsed;
            } else {
                $modifiedRecipe['bulk_fermentation_time'] = 0;
                $modifiedRecipe['final_proof_time'] = ($bulkTime + $proofTime) - $elapsed;
            }
        }
        
        // Baking
        if ($elapsed < $bulkTime + $proofTime + $bakeTime) {
            $modifiedRecipe['bake_time'] = $bakeTime;
        }
        
        if (!empty($modifiedRecipe)) {
            $scheduler->scheduleBreadProofingAlerts($timer->user_id, $modifiedRecipe);
        }
    }
}
