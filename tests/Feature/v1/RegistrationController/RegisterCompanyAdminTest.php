<?php

namespace Tests\Feature\v1\RegistrationController;

use App\Models\Company;
use App\Models\InviteToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegisterCompanyAdminTest extends TestCase
{
    use RefreshDatabase;

    protected $companyAdminRole;
    protected $employeeRole;
    protected $fullTimeRole;
    protected $dayShiftRole;
    protected $inviteToken;
    protected $expiredInviteToken;
    protected $companyAdmin;
    protected $company;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $this->companyAdminRole = Role::create(['name' => 'company_admin']);
        $this->employeeRole = Role::create(['name' => 'employee']);
        $this->fullTimeRole = Role::create(['name' => 'full_time']);
        $this->dayShiftRole = Role::create(['name' => 'day_shift']);

        // Create a sample company admin user and assign the roles
        $this->companyAdmin = User::create([
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
        $this->companyAdmin->assignRole($this->companyAdminRole);
        $this->companyAdmin->assignRole($this->fullTimeRole);
        $this->companyAdmin->assignRole($this->dayShiftRole);

        // Create a dummy company
        $this->company = Company::factory()->create(['user_id' => $this->companyAdmin->user_id]);

        // Attach company_id to company admin user
        $this->companyAdmin->update(['company_id' => $this->company->company_id]);

        $this->inviteToken = InviteToken::factory()->create([
            'company_id' => $this->company->company_id,
            'email' => 'user@example.com',
        ]);

        $this->expiredInviteToken = InviteToken::factory()->create([
            'company_id' => $this->company->company_id,
            'email' => 'user@example.com',
            'used_at' => Carbon::now(),
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up roles and other data if needed
        Role::whereIn('name', ['company_admin', 'full_time', 'day_shift'])->delete();
        User::whereIn('username', ['companyadmin'])->delete();
        Company::whereIn('user_id', [$this->companyAdmin->user_id])->delete();

        parent::tearDown();
    }

    // Main function
    public function testUserCanRegisterCompanyAdmin(): void
    {
        // Arrange user data
        $userData = [
            // User information
            'first_name' => 'User',
            'last_name' => 'Last Name',
            'place_of_birth' => 'Santa Rosa, Laguna',
            'date_of_birth' => '2002-05-18',
            'gender' => 'Male',
            'username' => 'user',
            'phone_number' => '0922-282-2828',
            'password' => 'password',
            'email' => 'user@example.com',
            'password_confirmation' => 'password',

            // Company information
            'company_name' => 'The Tech Company',
            'company_registration_number' => '12312x12xx1',
            'company_tax_id' => 'x123x23x423x',
            'company_address' => 'Blk. 13 Lot 24 Rosing Homes 1',
            'company_city' => 'Santa Rosa',
            'company_state' => 'Laguna',
            'company_postal_code' => '4026',
            'company_country' => 'Philippines',
            'company_phone_number' => '0921-272-2828',
            'company_email' => 'thetechcompany@example.com',
            'company_website' => 'http://www.example.com',
            'company_industry' => 'Information Technology',
            'company_founded_at' => '2024-08-19',
            'company_description' => 'The company was founded with the intention of being the next leader in the world.',
        ];

        // Perform a POST request to the registration route
        $response = $this->postJson(route('v1.register.company-admin'), $userData);

        // Assert that the response is successful
        $response->assertStatus(201);

        // Assert that the user was inserted into the database
        $this->assertDatabaseHas('users', [
            'first_name' => $userData['first_name'],
            'last_name' => $userData['last_name'],
            'email' => $userData['email'],
            'username' => $userData['username'],
        ]);

        // Assert that the company was inserted into the database
        $this->assertDatabaseHas('companies', [
            'company_name' => $userData['company_name'],
            'company_registration_number' => $userData['company_registration_number'],
            'company_tax_id' => $userData['company_tax_id'],
        ]);
    }

    public function testUserFailsRegisterCompanyAdminWithMissingFields(): void
    {
        // Arrange user data
        $userData = [
            // User information
            'first_name' => '',
            'last_name' => '',
            'place_of_birth' => '',
            'date_of_birth' => '',
            'gender' => '',
            'username' => '',
            'phone_number' => '',
            'password' => '',
            'email' => '',
            'password_confirmation' => '',

            // Company information
            'company_name' => '',
            'company_registration_number' => '',
            'company_tax_id' => '',
            'company_address' => '',
            'company_city' => '',
            'company_state' => '',
            'company_postal_code' => '',
            'company_country' => '',
            'company_phone_number' => '',
            'company_email' => '',
            'company_website' => '',
            'company_industry' => '',
            'company_founded_at' => '',
        ];

        // Perform a POST request to the registration route
        $response = $this->postJson(route('v1.register.company-admin'), $userData);

        // Assert that the registration was not successful
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'first_name',
                'last_name',
                'place_of_birth',
                'date_of_birth',
                'gender',
                'username',
                'phone_number',
                'password',
                'company_name',
                'company_registration_number',
                'company_tax_id',
                'company_address',
                'company_city',
                'company_state',
                'company_postal_code',
                'company_country',
                'company_phone_number',
                'company_email',
                'company_website',
                'company_industry',
                'company_founded_at',
            ]);
    }

    public function testUserCannotRegisterCompanyAdminWithDuplicateFields(): void
    {
        // Arrange user data that already exists
        $userData = [
            // User information
            'first_name' => $this->companyAdmin->first_name,
            'last_name' => $this->companyAdmin->last_name,
            'place_of_birth' => $this->companyAdmin->place_of_birth,
            'date_of_birth' => $this->companyAdmin->date_of_birth,
            'gender' => $this->companyAdmin->gender,
            'username' => $this->companyAdmin->username,
            'email' => $this->companyAdmin->email,
            'recovery_email' => $this->companyAdmin->recovery_email,
            'emergency_contact_name' => $this->companyAdmin->emergency_contact_name,
            'emergency_contact_number' => $this->companyAdmin->emergency_contact_number,
            'phone_number' => $this->companyAdmin->phone_number,
            'password' => 'password',
            'password_confirmation' => 'password',

            // Company information
            'company_name' => $this->company->company_name,
            'company_registration_number' => $this->company->company_registration_number,
            'company_tax_id' => $this->company->company_tax_id,
            'company_address' => $this->company->company_address,
            'company_city' => $this->company->company_city,
            'company_state' => $this->company->company_state,
            'company_postal_code' => $this->company->company_postal_code,
            'company_country' => $this->company->company_country,
            'company_phone_number' => $this->company->company_phone_number,
            'company_email' => $this->company->company_email,
            'company_website' => $this->company->company_website,
            'company_industry' => $this->company->company_industry,
            'company_founded_at' => $this->company->company_founded_at,
        ];

        // Perform a POST request to the registration route
        $response = $this->postJson(route('v1.register.company-admin'), $userData);

        // Assert that the registration was not successful
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'username',
                'phone_number',
                'company_name',
                'company_registration_number',
                'company_tax_id',
                'company_phone_number',
                'company_email',
            ]);
    }
}
