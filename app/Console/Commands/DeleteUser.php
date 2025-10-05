<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class DeleteUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:delete {--id=} {--email=} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete a user account';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userId = $this->option('id');
        $email = $this->option('email');
        $force = $this->option('force');

        // Find user by ID or email
        if ($userId) {
            $user = User::find($userId);
            if (! $user) {
                $this->error("User with ID {$userId} not found.");

                return Command::FAILURE;
            }
        } elseif ($email) {
            $user = User::where('email', $email)->first();
            if (! $user) {
                $this->error("User with email {$email} not found.");

                return Command::FAILURE;
            }
        } else {
            // Show list of users to choose from
            $users = User::orderBy('name')->get();

            if ($users->isEmpty()) {
                $this->info('No users found to delete.');

                return Command::SUCCESS;
            }

            $this->info('Available users:');
            $this->table(
                ['ID', 'Name', 'Email'],
                $users->map(fn ($u) => [$u->id, $u->name, $u->email])->toArray()
            );

            $selectedId = $this->ask('Enter the ID of the user to delete');
            $user = User::find($selectedId);

            if (! $user) {
                $this->error("User with ID {$selectedId} not found.");

                return Command::FAILURE;
            }
        }

        // Display user info
        $this->info('User to be deleted:');
        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $user->id],
                ['Name', $user->name],
                ['Email', $user->email],
                ['Starters', $user->starters()->count()],
                ['Created', $user->created_at->format('Y-m-d H:i:s')],
            ]
        );

        // Confirmation
        if (! $force) {
            if (! $this->confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                $this->info('Deletion cancelled.');

                return Command::SUCCESS;
            }
        }

        // Delete the user
        try {
            $userName = $user->name;
            $userEmail = $user->email;

            $user->delete();

            $this->info("User '{$userName}' ({$userEmail}) has been deleted successfully.");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to delete user: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
