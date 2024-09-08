<?php

namespace Database\Factories;

use App\Models\ProjectTask;
use App\Models\ProjectTaskSubtask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectTaskSubtask>
 */
class ProjectTaskSubtaskFactory extends Factory
{
    /**
     * The name of the model that is being generated.
     *
     * @var string
     */
    protected $model = ProjectTaskSubtask::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_task_id' => ProjectTask::factory(),
            'project_task_subtask_name' => $this->faker->sentence(3),
            'project_task_subtask_description' => $this->faker->paragraph(),
            'project_task_subtask_progress' => $this->faker->randomElement(config('constants.project_task_subtask_progress')),
            'project_task_subtask_priority_level' => $this->faker->randomElement(config('constants.project_task_subtask_priority_level')),
            'user_id' => null,
        ];
    }
}
