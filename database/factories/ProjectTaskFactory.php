<?php

namespace Database\Factories;

use App\Models\ProjectTask;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectTaskFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProjectTask::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'project_task_name' => $this->faker->sentence,
            'project_task_description' => $this->faker->paragraph,
            'project_task_progress' => $this->faker->randomElement(['Not started', 'In progress', 'Completed']),
            'project_task_priority_level' => $this->faker->randomElement(['Low', 'Medium', 'High']),
            'project_id' => function () {
                return \App\Models\Project::factory()->create()->project_id;
            },
        ];
    }
}
