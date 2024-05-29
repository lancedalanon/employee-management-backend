<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create 10 users with similar format as the student user
        User::factory()->count(10)->create();

        // Create a sample admin user
        User::create([
            'first_name' =>'Sample',
            'last_name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@mail.com',
            'password' => Hash::make('password123'), // You can hash the password using Hash::make
        ]);
    }
}
