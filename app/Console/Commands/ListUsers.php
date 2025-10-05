<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ListUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:list {--format=table : Output format (table|json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all user accounts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $users = User::orderBy('created_at', 'desc')->get();

        if ($users->isEmpty()) {
            $this->info('No users found.');

            return Command::SUCCESS;
        }

        $format = $this->option('format');

        if ($format === 'json') {
            $this->line(json_encode($users->toArray(), JSON_PRETTY_PRINT));
        } else {
            $this->info('Found '.$users->count().' user(s):');
            $this->newLine();

            $tableData = $users->map(function ($user) {
                return [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->created_at->format('Y-m-d H:i'),
                    $user->email_verified_at ? 'Yes' : 'No',
                ];
            })->toArray();

            $this->table(
                ['ID', 'Name', 'Email', 'Created', 'Verified'],
                $tableData
            );
        }

        return Command::SUCCESS;
    }
}
