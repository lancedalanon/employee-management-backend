<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Arr;

class DatabaseSeeder extends Seeder
{
     /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $employeeRole = Role::create(['name' => 'employee']);
        $internRole = Role::create(['name' => 'intern']);
        $companyAdminRole = Role::create(['name' => 'company_admin']);
        $fullTimeRole = Role::create(['name' => 'full_time']);
        $partTimeRole = Role::create(['name' => 'part_time']);
        $dayShiftRole = Role::create(['name' => 'day_shift']);
        $afternoonShiftRole = Role::create(['name' => 'afternoon_shift']);
        $eveningShiftRole = Role::create(['name' => 'evening_shift']);
        $earlyShiftRole = Role::create(['name' => 'early_shift']);
        $nightShiftRole = Role::create(['name' => 'night_shift']);

        // Define arrays for roles and shifts
        $employmentTypeRoles = [$fullTimeRole, $partTimeRole];
        $shiftRoles = [$dayShiftRole, $afternoonShiftRole, $eveningShiftRole, $earlyShiftRole, $nightShiftRole];
        $employeeRoles = [$employeeRole, $internRole];

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

        // Create a sample company admin user and assign the roles
        $companyUser = User::create([
            'first_name' => 'Lance',
            'last_name' => 'Dalanon',
            'place_of_birth' => 'Santa Rosa, Laguna',
            'date_of_birth' => '2002-05-18',
            'gender' => 'Male',
            'username' => 'user',
            'email' => 'example@gmail.com',
            'recovery_email' => 'examplerecovery@gmail.com',
            'emergency_contact_name' => 'Contact Person Name',
            'emergency_contact_number' => '0921-888-8888',
            'phone_number' => '0921-887-88887',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'company_id' => $company->company_id,
        ]);

        $companyUser->assignRole($employeeRole);
        $companyUser->assignRole($fullTimeRole);
        $companyUser->assignRole($dayShiftRole);

        // Create dummy users and assign roles
        User::factory()->count(10)->create(['company_id' => $company->company_id])->each(function ($user) use ($employmentTypeRoles, $shiftRoles, $employeeRoles) {
            // Randomly assign either 'full_time' or 'part_time'
            $employmentTypeRoles = Arr::random($employmentTypeRoles);
            $user->assignRole($employmentTypeRoles);

            // Randomly assign one of the shift roles
            $shiftRole = Arr::random($shiftRoles);
            $user->assignRole($shiftRole);

            // Randomly assign one of the employee roles
            $employeeRole = Arr::random($employeeRoles);
            $user->assignRole($employeeRole);
        });
    }
}
