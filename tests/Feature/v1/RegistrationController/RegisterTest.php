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

class RegisterTest extends TestCase
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
        $this->companyAdminRole = Role::create(['name' => 'company-admin']);
        $this->employeeRole = Role::create(['name' => 'employee']);
        $this->fullTimeRole = Role::create(['name' => 'full-time']);
        $this->dayShiftRole = Role::create(['name' => 'day-shift']);

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
        Role::whereIn('name', ['company-admin', 'full-time', 'day-shift'])->delete();
        User::whereIn('username', ['companyadmin'])->delete();
        Company::whereIn('user_id', [$this->companyAdmin->user_id])->delete();

        parent::tearDown();
    }

    // Main function
    public function testUserCanRegisterWithValidToken(): void
    {
        // Arrange user data
        $userData = [
            'first_name' => 'User',
            'last_name' => 'Last Name',
            'place_of_birth' => 'Santa Rosa, Laguna',
            'date_of_birth' => '2002-05-18',
            'gender' => 'Male',
            'username' => 'user',
            'phone_number' => '0922-282-2828',
            'password' => 'password',
            'password_confirmation' => 'password',
            'employment_type' => 'full-time',
            'shift' => 'day-shift',
            'role' => 'employee',
            'token' => $this->inviteToken->token,
        ];

        // Perform a POST request to the registration route
        $response = $this->postJson(route('v1.register'), $userData);

        // Assert that the response is successful
        $response->assertStatus(201);

        // Verify that the user was created in the database
        $this->assertDatabaseHas('users', [
            'username' => 'user',
            'email' => 'user@example.com',
        ]);
    }

    // Validations
    public function testUserFailsRegisterWithInvalidToken(): void
    {
        // Arrange user data
        $userData = [
            'first_name' => 'User',
            'last_name' => 'Last Name',
            'place_of_birth' => 'Santa Rosa, Laguna',
            'date_of_birth' => '2002-05-18',
            'gender' => 'Male',
            'username' => 'user',
            'phone_number' => '0922-282-2828',
            'password' => 'password',
            'password_confirmation' => 'password',
            'employment_type' => 'full-time',
            'shift' => 'day-shift',
            'role' => 'employee',
            'token' => $this->expiredInviteToken->token, // Expired token
        ];

        // Perform a POST request to the registration route
        $response = $this->postJson(route('v1.register'), $userData);

        // Assert that the response is successful
        $response->assertStatus(400)
            ->assertJson([
                'message' => 'This token is invalid or has expired.',
            ]);
    }

    public function testUserFailsWithMissingFields(): void
    {
        // Arrange user data missing all fields
        $userData = [
            'first_name' => '',
            'last_name' => '',
            'place_of_birth' => '',
            'date_of_birth' => '',
            'gender' => '',
            'username' => '',
            'phone_number' => '',
            'password' => '',
            'password_confirmation' => '',
            'employment_type' => '',
            'shift' => '',
            'role' => '',
            'token' => '',
        ];

        // Perform a POST request to the registration route
        $response = $this->postJson(route('v1.register'), $userData);

        // Assert that the response is successful
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
                'employment_type',
                'shift',
                'role',
                'token',
            ]);
    }
}
