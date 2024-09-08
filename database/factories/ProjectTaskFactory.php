<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectTask;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Config;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectTask>
 */
class ProjectTaskFactory extends Factory
{
    /**
     * The name of the model that is being generated.
     *
     * @var string
     */
    protected $model = ProjectTask::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'project_task_name' => $this->faker->sentence(3),
            'project_task_description' => $this->faker->paragraph(),
            'project_task_progress' => $this->faker->randomElement(config('constants.project_task_progress')),
            'project_task_priority_level' => $this->faker->randomElement(config('constants.project_task_priority_level')),
            'user_id' => null,
        ];
    }
}
