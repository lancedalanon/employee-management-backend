<?php

namespace Database\Factories;

use App\Models\Dtr;
use App\Models\DtrBreak;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DtrBreak>
 */
class DtrBreakFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DtrBreak::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dtr_id' => function () {
                return Dtr::factory()->create()->id;
            },
            'break_time' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'resume_time' => $this->faker->dateTimeBetween('now', '+1 hour'), // Resume within 1 hour
        ];
    }
}
