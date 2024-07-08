<?php

namespace Database\Factories;

use App\Models\Dtr;
use App\Models\DtrBreak;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'dtr_id' => Dtr::factory(),
            'break_time' => Carbon::now(),
            'resume_time' => null,
        ];
    }

    /**
     * Set the break_time value.
     *
     * @param Carbon $breakTime
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withBreakTime(Carbon $breakTime)
    {
        return $this->state([
            'break_time' => $breakTime,
        ]);
    }

    /**
     * Set the resume_time value.
     *
     * @param Carbon $resumeTime
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withResumeTime(Carbon $resumeTime)
    {
        return $this->state([
            'resume_time' => $resumeTime,
        ]);
    }
}
