<?php

namespace Database\Factories;

use App\Models\ProjectTaskSubtask;
use App\Models\ProjectTaskSubtaskStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectTaskSubtaskStatus>
 */
class ProjectTaskSubtaskStatusFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProjectTaskSubtaskStatus::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_task_subtask_id' => ProjectTaskSubtask::factory(),
            'project_task_subtask_status' => $this->faker->word,
            'project_task_subtask_status_media_file' => $this->faker->filePath(),
        ];
    }
}
