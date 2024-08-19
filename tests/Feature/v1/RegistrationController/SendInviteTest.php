<?php

namespace Tests\Feature\v1\RegistrationController;

use App\Models\Company;
use App\Models\InviteToken;
use App\Models\User;
use App\Notifications\InviteNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SendInviteTest extends TestCase
{
    use RefreshDatabase;

    protected $companyAdminRole;
    protected $employeeRole;
    protected $fullTimeRole;
    protected $dayShiftRole;
    protected $inviteToken;
    protected $companyAdmin;
    protected $company;
    protected $user;

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

        // Create dummy user
        $this->user = User::factory()->withRoles()->create();

        // Authenticate company admin user
        Sanctum::actingAs($this->companyAdmin);
    }

    protected function tearDown(): void
    {
        // Clean up roles and other data if needed
        Role::whereIn('name', ['company-admin', 'full-time', 'day-shift'])->delete();
        User::whereIn('username', ['companyadmin'])->delete();
        Company::whereIn('user_id', [$this->companyAdmin->user_id])->delete();

        parent::tearDown();
    }

    public function testCompanyAdminCanSendInviteEmail(): void
    {
        // Arrange
        $email = 'newemployee@example.com'; // Ensure this email is not already in use

        // Act
        $response = $this->postJson(route('v1.companyAdmin.sendInvite'), [
            'email' => $email,
        ]);

        // Assert
        $response->assertStatus(201);

        // Check that the InviteToken was created
        $this->assertDatabaseHas('invite_tokens', [
            'email' => $email,
        ]);
    }

    public function testCompanyAdminFailsSendInviteEmailToExistingUser(): void
    {
        // Act
        $response = $this->postJson(route('v1.companyAdmin.sendInvite'), [
            'email' => $this->user->email,
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'email',
            ]);
    }

    public function testCompanyAdminFailsSendInviteEmailWithMissingField(): void
    {
        // Act
        $response = $this->postJson(route('v1.companyAdmin.sendInvite'), [
            'email' => '',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'email',
            ]);
    }
}
