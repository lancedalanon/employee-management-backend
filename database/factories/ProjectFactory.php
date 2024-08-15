<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'project_name' => $this->faker->sentence,
            'project_description' => $this->faker->paragraph,
        ];
    }

    /**
     * Configure the User relationship for the project.
     *
     * @param  int  $userCount
     * @return $this
     */
    public function withUsers($userCount = 10)
    {
        return $this->hasAttached(User::factory()->count($userCount), [], 'users');
    }
}
