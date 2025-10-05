<?php

namespace App\Console\Commands;

use App\Services\NotificationSchedulerService;
use Illuminate\Console\Command;

class CleanupOrphanedNotifications extends Command
{
    protected $signature = 'notifications:cleanup-orphaned
                           {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Clean up orphaned notifications for deleted starters';

    public function handle(): void
    {
        $isDryRun = $this->option('dry-run');

        $this->info('🧹 Cleaning up orphaned notifications...');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No notifications will actually be deleted');
        }

        $notificationService = app(NotificationSchedulerService::class);

        if ($isDryRun) {
            // For dry run, we'd need to create a separate method that returns info without deleting
            $this->warn('Dry run mode not yet implemented. Run without --dry-run to perform cleanup.');

            return;
        }

        $deletedCount = $notificationService->cleanupOrphanedNotifications();

        if ($deletedCount > 0) {
            $this->info("✅ Cleaned up {$deletedCount} orphaned notifications");
        } else {
            $this->info('✅ No orphaned notifications found');
        }
    }
}
