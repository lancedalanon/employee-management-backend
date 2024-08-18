<?php

namespace Database\Seeders;

use App\Models\Company;
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
        $superRole = Role::create(['name' => 'super']);
        $adminRole = Role::create(['name' => 'admin']);
        $employeeRole = Role::create(['name' => 'employee']);
        $internRole = Role::create(['name' => 'intern']);
        $companyAdminRole = Role::create(['name' => 'company-admin']);
        $fullTimeRole = Role::create(['name' => 'full-time']);
        $partTimeRole = Role::create(['name' => 'part-time']);
        $dayShiftRole = Role::create(['name' => 'day-shift']);
        $afternoonShiftRole = Role::create(['name' => 'afternoon-shift']);
        $eveningShiftRole = Role::create(['name' => 'evening-shift']);
        $earlyShiftRole = Role::create(['name' => 'early-shift']);
        $lateShiftRole = Role::create(['name' => 'late-shift']);

        // Create admin user
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

        $admin->assignRole($adminRole);
        
        // Create a sample company admin user and assign the roles
        $companyAdmin = User::create([
            'first_name' => 'Company',
            'last_name' => 'Admin',
            'place_of_birth' => 'Santa Rosa, Laguna',
            'date_of_birth' => '2002-05-18',
            'gender' => 'Male',
            'username' => 'companyadmin',
            'email' => 'companyadmin@example.com',
            'recovery_email' => 'companyadmin1@example.com',
            'emergency_contact_name' => 'Contact Person Name',
            'emergency_contact_number' => '0921-277-2222',
            'phone_number' => '0921-212-2777',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $companyAdmin->assignRole($companyAdminRole);
        $companyAdmin->assignRole($fullTimeRole);
        $companyAdmin->assignRole($dayShiftRole);

        // Create dummy company
        $company = Company::factory()->create(['user_id' => $companyAdmin->user_id]);

        // Attach company_id to company admin user
        $companyAdmin->update(['company_id' => $company->company_id]);

        // Create dummy users
        User::factory()->count(10)->create(['company_id' => $company->company_id]);
    }
}
