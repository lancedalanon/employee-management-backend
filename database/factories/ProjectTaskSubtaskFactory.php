<?php

namespace Database\Factories;

use App\Models\ProjectTaskSubtask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectTaskSubtask>
 */
class ProjectTaskSubtaskFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProjectTaskSubtask::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'project_task_subtask_name' => $this->faker->sentence,
            'project_task_subtask_description' => $this->faker->paragraph,
            'project_task_subtask_progress' => $this->faker->randomElement(['Not started', 'In progress', 'Reviewing', 'Completed']),
            'project_task_subtask_priority_level' => $this->faker->randomElement(['Low', 'Medium', 'High']),
            'project_task_id' => function () {
                return \App\Models\ProjectTask::factory()->create()->project_task_id;
            },
            'user_id' => function () {
                return \App\Models\User::factory()->create()->user_id;
            },
        ];
    }
}
