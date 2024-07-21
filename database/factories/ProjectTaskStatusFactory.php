<?php

namespace Database\Factories;

use App\Models\ProjectTask;
use App\Models\ProjectTaskStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectTaskStatusFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProjectTaskStatus::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'project_task_id' => \App\Models\ProjectTask::inRandomOrder()->first()->id ?? null,
            'project_task_status' => $this->faker->word,
            'project_task_status_media_file' => $this->faker->filePath(),
        ];
    }
}
