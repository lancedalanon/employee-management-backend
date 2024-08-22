<?php

namespace Tests\Feature\v1;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected $adminRole;
    protected $companyAdminRole;
    protected $fullTimeRole;
    protected $dayShiftRole;
    protected $admin;
    protected $adminToken;
    protected $companyAdmin;
    protected $companyAdminToken;
    protected $user;
    protected $userToken;
    protected $company;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $this->adminRole = Role::create(['name' => 'admin']);
        $this->companyAdminRole = Role::create(['name' => 'company_admin']);
        $this->fullTimeRole = Role::create(['name' => 'full_time']);
        $this->dayShiftRole = Role::create(['name' => 'day_shift']);

        // Create a sample admin user and assign the roles
        $this->admin = User::create([
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
        $this->admin->assignRole($this->adminRole);

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

        // Create dummy user
        $this->user = User::factory()->create(['company_id' => $this->company->company_id]);
    }

    protected function tearDown(): void
    {
        // Clean up roles and other data if needed
        Role::whereIn('name', ['admin', 'company_admin', 'full_time', 'day_shift'])->delete();
        User::whereIn('username', ['admin', 'companyadmin', $this->user->username])->delete();
        Company::whereIn('user_id', [$this->companyAdmin->user_id])->delete();

        parent::tearDown();
    }

    // Admin Tests
    public function testAdminCanLogin(): void
    {
        $response = $this->postJson(route('v1.login'), [
            'username' => $this->admin->username,
            'password' => 'password',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'data' => [
                         'token',
                         'username',
                     ],
                 ]);

        $this->adminToken = $response->json('data.token');
    }

    public function testAdminCanLogout(): void
    {
        $response = $this->postJson(route('v1.login'), [
            'username' => $this->admin->username,
            'password' => 'password',
        ]);

        $token = $response->json('data.token');

        $response = $this->withToken($token)->postJson(route('v1.logout'));

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'User logged out successfully.',
                 ]);
    }

    // Company Admin Tests
    public function testCompanyAdminCanLogin(): void
    {
        $response = $this->postJson(route('v1.login'), [
            'username' => $this->companyAdmin->username,
            'password' => 'password',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'data' => [
                         'token',
                         'username',
                     ],
                 ]);

        $this->companyAdminToken = $response->json('data.token');
    }

    public function testCompanyAdminCanLogout(): void
    {
        $response = $this->postJson(route('v1.login'), [
            'username' => $this->companyAdmin->username,
            'password' => 'password',
        ]);

        $token = $response->json('data.token');

        $response = $this->withToken($token)->postJson(route('v1.logout'));

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'User logged out successfully.',
                 ]);
    }

    // User Tests
    public function testUserCanLogin(): void
    {
        $response = $this->postJson(route('v1.login'), [
            'username' => $this->user->username,
            'password' => 'password',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'data' => [
                         'token',
                         'username',
                     ],
                 ]);

        $this->userToken = $response->json('data.token');
    }

    public function testUserCanLogout(): void
    {
        $response = $this->postJson(route('v1.login'), [
            'username' => $this->user->username,
            'password' => 'password',
        ]);

        $token = $response->json('data.token');

        $response = $this->withToken($token)->postJson(route('v1.logout'));

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'User logged out successfully.',
                 ]);
    }

    // Validation Tests
    public function testLoginFailsWithInvalidCredentials(): void
    {
        $response = $this->postJson(route('v1.login'), [
            'username' => 'invalid_user',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'Invalid credentials.',
                 ]);
    }

    public function testLoginFailsWithoutUsername(): void
    {
        $response = $this->postJson(route('v1.login'), [
            'password' => 'password',
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'Invalid credentials.',
                 ]);
    }

    public function testLoginFailsWithoutPassword(): void
    {
        $response = $this->postJson(route('v1.login'), [
            'username' => $this->admin->username,
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'Invalid credentials.',
                 ]);
    }
}
