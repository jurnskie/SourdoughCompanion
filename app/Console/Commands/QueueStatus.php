<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class QueueStatus extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'queue:status';

    /**
     * The console command description.
     */
    protected $description = 'Check queue status and show pending/failed jobs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Queue Status Report');
        $this->line('==================');

        // Check pending jobs
        $pendingJobs = DB::table('jobs')->count();
        $this->line("Pending jobs: {$pendingJobs}");

        if ($pendingJobs > 0) {
            $this->warn("⚠️  There are {$pendingJobs} jobs waiting to be processed");
            $this->info("Run: php artisan queue:work --daemon");
        } else {
            $this->info("✅ No pending jobs in queue");
        }

        // Check failed jobs
        $failedJobs = DB::table('failed_jobs')->count();
        $this->line("Failed jobs: {$failedJobs}");

        if ($failedJobs > 0) {
            $this->error("❌ There are {$failedJobs} failed jobs");
            $this->info("Run: php artisan queue:retry all");
        } else {
            $this->info("✅ No failed jobs");
        }

        // Show recent job types if pending
        if ($pendingJobs > 0 && $pendingJobs <= 10) {
            $this->line('');
            $this->info('Recent pending jobs:');
            $jobs = DB::table('jobs')
                ->select('payload')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            foreach ($jobs as $job) {
                $payload = json_decode($job->payload, true);
                $command = $payload['displayName'] ?? 'Unknown';
                $this->line("  • {$command}");
            }
        }

        return self::SUCCESS;
    }
}