<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DefaultUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::firstOrCreate(
            ['email' => 'sourdough@localhost'],
            [
                'name' => 'Sourdough Baker',
                'password' => bcrypt('starter123'), // Default password, not used in single-user mode
                'email_verified_at' => now(),
            ]
        );
    }
}
