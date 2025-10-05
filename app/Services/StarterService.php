<?php

namespace App\Services;

use App\Models\Feeding;
use App\Models\Starter;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StarterService
{
    public function createStarter(string $name = 'My Sourdough Starter', string $flourType = 'whole wheat'): Starter
    {
        $user = $this->getDefaultUser();

        if (! $user) {
            throw new \InvalidArgumentException('No user available to create a starter');
        }

        $starter = $user->starters()->create([
            'name' => $name,
            'flour_type' => $flourType,
        ]);

        // Add initial feeding
        $this->addFeeding($starter, 50, 50, 50, '1:1:1');

        return $starter;
    }

    public function addFeeding(Starter $starter, int $starterAmount, int $flourAmount, int $waterAmount, ?string $ratio = null, ?UploadedFile $photo = null, bool $force = false): Feeding
    {
        if (! $force) {
            $canFeed = $starter->canFeedNow();
            if (! $canFeed['can_feed']) {
                throw new \InvalidArgumentException($canFeed['reason']);
            }
        }

        $previousPhase = $starter->getCurrentPhase();

        // Handle photo upload if provided
        $photoPath = null;
        if ($photo) {
            $photoPath = $this->storePhoto($photo, $starter->id);
        }

        $feeding = $starter->feedings()->create([
            'day' => $starter->getCurrentDay(),
            'starter_amount' => $starterAmount,
            'flour_amount' => $flourAmount,
            'water_amount' => $waterAmount,
            'ratio' => $ratio ?? "{$starterAmount}:{$flourAmount}:{$waterAmount}",
            'photo_path' => $photoPath,
        ]);

        // Refresh starter to get updated phase after feeding
        $starter->refresh();
        $currentPhase = $starter->getCurrentPhase();

        // Schedule notifications after feeding
        $notificationScheduler = app(NotificationSchedulerService::class);

        // Schedule next feeding reminders
        $notificationScheduler->scheduleFeedingReminders($starter);

        // Check for phase transitions
        if ($previousPhase !== $currentPhase) {
            $notificationScheduler->checkAndSchedulePhaseTransitions($starter);
        }

        return $feeding;
    }

    public function getFeedingStatistics(Starter $starter): array
    {
        $feedings = $starter->feedings;

        if ($feedings->isEmpty()) {
            return [
                'total_feedings' => 0,
                'average_ratio' => null,
                'total_flour_used' => 0,
                'total_water_used' => 0,
                'consistency_score' => 0,
            ];
        }

        $totalFlour = $feedings->sum('flour_amount');
        $totalWater = $feedings->sum('water_amount');
        $totalFeedings = $feedings->count();

        // Calculate consistency score based on regular feeding pattern
        $consistencyScore = $this->calculateConsistencyScore($feedings);

        return [
            'total_feedings' => $totalFeedings,
            'total_flour_used' => $totalFlour,
            'total_water_used' => $totalWater,
            'average_hydration' => $totalFlour > 0 ? round(($totalWater / $totalFlour) * 100, 1) : 0,
            'consistency_score' => $consistencyScore,
        ];
    }

    private function calculateConsistencyScore($feedings): int
    {
        if ($feedings->count() < 2) {
            return 0;
        }

        $intervals = [];
        $previousFeeding = null;

        foreach ($feedings as $feeding) {
            if ($previousFeeding) {
                $intervals[] = $feeding->created_at->diffInHours($previousFeeding->created_at);
            }
            $previousFeeding = $feeding;
        }

        if (empty($intervals)) {
            return 0;
        }

        $averageInterval = array_sum($intervals) / count($intervals);
        $variance = 0;

        foreach ($intervals as $interval) {
            $variance += pow($interval - $averageInterval, 2);
        }

        $variance /= count($intervals);
        $standardDeviation = sqrt($variance);

        // Lower standard deviation = higher consistency
        $consistencyScore = max(0, 100 - ($standardDeviation * 2));

        return (int) round($consistencyScore);
    }

    public function calculateBreadRecipe(Starter $starter, array $options = []): array
    {
        $flourWeight = $options['flour_weight'] ?? 500; // grams
        $loaves = $options['loaves'] ?? 1;
        $temperature = $options['temperature'] ?? 22; // Celsius
        $recipeType = $options['recipe_type'] ?? 'basic';
        $humidityLevel = $options['humidity_level'] ?? 'normal';

        // Calculate starter percentage based on temperature
        $starterPercentage = match (true) {
            $temperature >= 26 => 0.10, // Hot kitchen, less starter
            $temperature >= 22 => 0.15, // Normal temperature
            default => 0.20, // Cold kitchen, more starter
        };

        // Adjust for recipe type
        $baseHydration = match ($recipeType) {
            'high-hydration' => 0.80,
            'whole-grain' => 0.75,
            default => 0.70, // basic
        };

        // Adjust hydration based on humidity level
        $hydrationAdjustment = match ($humidityLevel) {
            'dry' => 0.02, // Add 2% more water in dry conditions
            'humid' => -0.02, // Reduce 2% water in humid conditions
            default => 0.00, // No adjustment for normal humidity
        };

        $hydrationPercentage = $baseHydration + $hydrationAdjustment;

        $starterAmount = (int) round($flourWeight * $starterPercentage);
        $waterAmount = (int) round($flourWeight * $hydrationPercentage);
        $saltAmount = (int) round($flourWeight * 0.02); // 2% salt

        // Calculate timing based on temperature and starter health
        $phase = $starter->getCurrentPhase();
        $bulkFermentationHours = match (true) {
            $temperature >= 26 => 3,
            $temperature >= 22 => 4,
            default => 5,
        };

        if ($phase === 'creation') {
            $bulkFermentationHours += 1; // Younger starter takes longer
        }

        // Adjust timing based on humidity
        $timingAdjustment = match ($humidityLevel) {
            'humid' => -0.5, // Faster fermentation in humid conditions
            'dry' => 0.5, // Slower fermentation in dry conditions
            default => 0,
        };

        $adjustedBulkHours = max(2, $bulkFermentationHours + $timingAdjustment);

        return [
            'ingredients' => [
                'flour' => $flourWeight * $loaves,
                'water' => $waterAmount * $loaves,
                'starter' => $starterAmount * $loaves,
                'salt' => $saltAmount * $loaves,
            ],
            'percentages' => [
                'hydration' => $hydrationPercentage * 100,
                'starter' => $starterPercentage * 100,
                'salt' => 2.0,
            ],
            'timing' => [
                'bulk_fermentation_hours' => $adjustedBulkHours,
                'final_proof_hours' => 2,
                'total_time_hours' => $adjustedBulkHours + 2 + 1, // +1 for shaping
            ],
            'environment' => [
                'temperature' => $temperature,
                'humidity_level' => $humidityLevel,
                'recipe_type' => $recipeType,
            ],
            'loaves' => $loaves,
            'adjustments' => [
                'hydration_adjustment' => $hydrationAdjustment * 100,
                'timing_adjustment' => $timingAdjustment,
            ],
        ];
    }

    public function getActiveStarterForUser(?User $user = null): ?Starter
    {
        $user = $user ?? $this->getDefaultUser();

        if (! $user) {
            return null;
        }

        return $user->activeStarter();
    }

    public function resetStarter(Starter $starter, ?string $reason = null, bool $userInitiated = true): Starter
    {
        // Determine reset reason based on context
        if (! $reason) {
            $healthStatus = $starter->getHealthStatus();
            $isHealthy = in_array($healthStatus['status'], ['excellent', 'good']);

            if ($userInitiated && $isHealthy) {
                $reason = 'User-initiated reset (starter was healthy)';
            } elseif ($userInitiated && ! $isHealthy) {
                $reason = "User-initiated reset (starter status: {$healthStatus['status']})";
            } else {
                $reason = 'Automatic reset due to poor health or neglect';
            }
        }

        // Archive the old starter by adding a note with detailed information
        $resetNote = '[RESET '.now()->format('Y-m-d H:i').'] '.$reason;
        $healthInfo = "\nHealth at reset: ".$starter->getHealthStatus()['message'];
        $feedingInfo = "\nTotal feedings: ".$starter->feedings()->count();
        $ageInfo = "\nAge: ".$starter->getCurrentDay().' days';

        $starter->update([
            'notes' => ($starter->notes ? $starter->notes."\n\n" : '').
                      $resetNote.$healthInfo.$feedingInfo.$ageInfo,
        ]);

        // Create a new starter with the same name and flour type
        $newStarter = $this->createStarter(
            $starter->name.' (Reset)',
            $starter->flour_type
        );

        return $newStarter;
    }

    public function canResetStarter(Starter $starter): array
    {
        $lastFeeding = $starter->feedings()->latest()->first();

        if (! $lastFeeding) {
            return [
                'can_reset' => false,
                'reason' => 'Cannot reset a starter with no feedings',
            ];
        }

        $daysSinceLastFeeding = $lastFeeding->created_at->diffInDays(now());
        $healthStatus = $starter->getHealthStatus();

        // Always allow reset, but provide different messaging based on health
        $isHealthy = in_array($healthStatus['status'], ['excellent', 'good']);
        $recommendedReset = $healthStatus['status'] === 'poor' || $daysSinceLastFeeding >= 7;

        return [
            'can_reset' => true,
            'reason' => null,
            'health_status' => $healthStatus,
            'days_since_feeding' => $daysSinceLastFeeding,
            'is_healthy' => $isHealthy,
            'recommended_reset' => $recommendedReset,
            'warning_message' => $isHealthy
                ? 'Your starter appears to be healthy. Are you sure you want to reset it?'
                : 'Resetting is recommended due to poor starter health.',
        ];
    }

    /**
     * Store photo for feeding
     */
    private function storePhoto(UploadedFile $photo, int $starterId): string
    {
        // Basic validation
        if (! $photo->isValid()) {
            throw new \InvalidArgumentException('Invalid photo upload');
        }

        // Check file size (max 5MB)
        if ($photo->getSize() > 5 * 1024 * 1024) {
            throw new \InvalidArgumentException('Photo file size must be less than 5MB');
        }

        // Check if it's an image
        if (! str_starts_with($photo->getMimeType(), 'image/')) {
            throw new \InvalidArgumentException('File must be an image');
        }

        // Generate unique filename with timestamp
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "starter_{$starterId}_feeding_{$timestamp}.".$photo->getClientOriginalExtension();

        // Store in public disk under feeding-photos directory
        $path = $photo->storeAs('feeding-photos', $filename, 'public');

        return $path;
    }

    /**
     * Delete a starter and clean up associated notifications
     */
    public function deleteStarter(Starter $starter): bool
    {
        // Cancel any scheduled notifications for this starter
        $notificationScheduler = app(NotificationSchedulerService::class);
        $notificationScheduler->cancelFeedingReminders($starter);

        // Delete associated photos
        $this->deleteStarterPhotos($starter);

        // Delete the starter (cascade will handle feedings)
        return $starter->delete();
    }

    /**
     * Clear all notification schedules for a user
     */
    public function clearAllNotifications(User $user): int
    {
        $notificationScheduler = app(NotificationSchedulerService::class);

        return $notificationScheduler->clearAllUserNotifications($user->id);
    }

    /**
     * Get all scheduled notifications for a user
     */
    public function getUserNotifications(User $user): array
    {
        $notificationScheduler = app(NotificationSchedulerService::class);

        return $notificationScheduler->getUserNotifications($user->id);
    }

    /**
     * Delete a specific notification
     */
    public function deleteNotification(int $jobId): bool
    {
        $notificationScheduler = app(NotificationSchedulerService::class);

        return $notificationScheduler->deleteNotification($jobId);
    }

    /**
     * Delete multiple notifications
     */
    public function deleteNotifications(array $jobIds): int
    {
        $notificationScheduler = app(NotificationSchedulerService::class);

        return $notificationScheduler->deleteNotifications($jobIds);
    }

    /**
     * Update notification schedule time
     */
    public function updateNotificationSchedule(int $jobId, \Carbon\Carbon $newScheduleTime): bool
    {
        $notificationScheduler = app(NotificationSchedulerService::class);

        return $notificationScheduler->updateNotificationSchedule($jobId, $newScheduleTime);
    }

    /**
     * Clean up orphaned notifications for deleted starters
     */
    public function cleanupOrphanedNotifications(): int
    {
        $notificationScheduler = app(NotificationSchedulerService::class);

        return $notificationScheduler->cleanupOrphanedNotifications();
    }

    /**
     * Delete all photos associated with a starter
     */
    private function deleteStarterPhotos(Starter $starter): void
    {
        $feedings = $starter->feedings;

        foreach ($feedings as $feeding) {
            if ($feeding->photo_path && Storage::disk('public')->exists($feeding->photo_path)) {
                Storage::disk('public')->delete($feeding->photo_path);
            }
        }
    }

    /**
     * Get the authenticated user or the default user for the application
     */
    private function getDefaultUser(): ?User
    {
        // First try to get the authenticated user
        if (auth()->check()) {
            return auth()->user();
        }

        // Fallback for CLI/testing contexts
        return User::where('email', 'sourdough@localhost')->first() ?? User::first();
    }
}
