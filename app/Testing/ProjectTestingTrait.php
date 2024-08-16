<?php

namespace App\Testing;

use App\Models\Project;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

trait ProjectTestingTrait
{
    protected $admin;

    protected $project;

    /**
     * Set up the test environment.
     */
    protected function setUpProject(): void
    {
        // Create admin role
        $adminRole = Role::create(['name' => 'company-admin']);

        // Create a dummy admin user
        $this->admin = User::factory()->create();
        $this->admin->assignRole($adminRole);

        // Authenticate the admin user
        Sanctum::actingAs($this->admin);

        // Create projects with users
        $this->project = Project::factory()->count(3)->withUsers(5)->create();
    }

    /**
     * Tear down the test environment.
     */
    protected function tearDownProject(): void
    {
        //
    }
}
