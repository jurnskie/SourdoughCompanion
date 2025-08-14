<?php

namespace App\Console\Commands;

use App\Services\NotificationSchedulerService;
use Illuminate\Console\Command;

class CheckStarterHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'starter:check-health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check all starters health and send notifications if needed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking starter health...');

        $notificationScheduler = app(NotificationSchedulerService::class);
        $notificationScheduler->scheduleHealthCheckReminders();

        $this->info('Starter health check completed');
    }
}
