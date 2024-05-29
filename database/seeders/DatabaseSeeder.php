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
            'last_name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@mail.com',
            'password' => Hash::make('password123'),
        ]);

        // Assign the admin role to the admin user
        $admin->assignRole($adminRole);
    }
}
