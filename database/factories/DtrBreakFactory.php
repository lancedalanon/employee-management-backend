<?php

namespace Database\Factories;

use App\Models\Dtr;
use App\Models\DtrBreak;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories.Factory<\App\Models\DtrBreak>
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
            'dtr_id' => Dtr::factory(), // Generate a random DTR ID
            'dtr_break_break_time' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'dtr_break_resume_time' => $this->faker->optional()->dateTimeBetween($this->state['dtr_break_break_time'] ?? now(), $this->state['dtr_break_break_time'] ?? now()->addHours(2)),
        ];
    }

    /**
     * Specify the break time for the factory.
     *
     * @param \DateTime|string|null $breakTime
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withBreakTime($breakTime = null): self
    {
        return $this->state(function (array $attributes) use ($breakTime) {
            $breakTime = $breakTime ? Carbon::parse($breakTime) : now();
            return [
                'dtr_break_break_time' => $breakTime,
                'dtr_break_resume_time' => $this->faker->optional()->dateTimeBetween($breakTime, $breakTime->copy()->addHours(2)),
            ];
        });
    }

    /**
     * Specify the resume time for the factory.
     *
     * @param \DateTime|string|null $resumeTime
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withResumeTime($resumeTime = null): self
    {
        return $this->state(function (array $attributes) use ($resumeTime) {
            $resumeTime = $resumeTime ? Carbon::parse($resumeTime) : now();
            return [
                'dtr_break_resume_time' => $resumeTime,
                'dtr_break_break_time' => $this->faker->optional()->dateTimeBetween($resumeTime->subHours(2), $resumeTime),
            ];
        });
    }

    /**
     * Specify that only break time should be created.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function onlyBreakTime(): self
    {
        return $this->state(function (array $attributes) {
            $breakTime = $attributes['dtr_break_break_time'] ?? now();
            return [
                'dtr_break_break_time' => $breakTime,
                'dtr_break_resume_time' => null, // Resume time should not be set
            ];
        });
    }

    /**
     * Specify that only resume time should be created.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function onlyResumeTime(): self
    {
        return $this->state(function (array $attributes) {
            $resumeTime = $attributes['dtr_break_resume_time'] ?? now();
            return [
                'dtr_break_break_time' => null, // Break time should not be set
                'dtr_break_resume_time' => $resumeTime,
            ];
        });
    }
}
