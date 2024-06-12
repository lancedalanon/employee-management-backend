<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $studentRole = Role::create(['name' => 'student']);

        // Create 10 student users
        $students = User::factory()->count(10)->create();
        foreach ($students as $student) {
            $student->assignRole($studentRole);
        }

        // Create a sample admin user
        $admin = User::create([
            'first_name' => 'Sample',
            'middle_name' => 'User',
            'last_name' => 'Admin',
            'place_of_birth' => 'Santa Rosa, Laguna',
            'date_of_birth' => '2002-05-18',
            'gender' => 'male',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        // Assign the admin role to the admin user
        $admin->assignRole($adminRole);
    }
}
