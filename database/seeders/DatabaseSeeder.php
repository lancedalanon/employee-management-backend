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
        $fullTimeRole = Role::create(['name' => 'full-time']);
        $partTimeRole = Role::create(['name' => 'part-time']);
        $dayShiftRole = Role::create(['name' => 'day-shift']);
        $afternoonShiftRole = Role::create(['name' => 'afternoon-shift']);
        $eveningShiftRole = Role::create(['name' => 'evening-shift']);
        $earlyShiftRole = Role::create(['name' => 'early-shift']);
        $lateShiftRole = Role::create(['name' => 'late-shift']);

        // Create 10 student users and shuffle their roles
        $students = User::factory()->count(10)->create();
        foreach ($students as $student) {
            $shiftRoles = [$dayShiftRole, $afternoonShiftRole, $eveningShiftRole, $earlyShiftRole, $lateShiftRole];
            shuffle($shiftRoles);
            $student->assignRole($shiftRoles[0]);

            $jobTypeRoles = [$fullTimeRole, $partTimeRole];
            shuffle($jobTypeRoles);
            $student->assignRole($jobTypeRoles[0]);

            $student->assignRole($studentRole);
        }

        // Create a sample admin user and assign the roles
        $admin = User::create([
            'first_name' => 'Sample',
            'middle_name' => 'User',
            'last_name' => 'Admin',
            'place_of_birth' => 'Santa Rosa, Laguna',
            'date_of_birth' => '2002-05-18',
            'gender' => 'Male',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'recovery_email' => 'admin1@example.com',
            'emergency_contact_name' => 'Contact Person Name',
            'emergency_contact_number' => '0921-288-2222',
            'phone_number' => '0921-212-2227',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $admin->assignRole($dayShiftRole);
        $admin->assignRole($fullTimeRole);
        $admin->assignRole($adminRole);
    }
}
