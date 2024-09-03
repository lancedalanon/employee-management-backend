<?php

namespace Database\Factories;

use App\Models\Dtr;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Dtr>
 */
class DtrFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Dtr::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(), // Generate a random user ID
        ];
    }

    /**
     * Specify that only time in should be created.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withTimeIn(): self
    {
        return $this->state(function (array $attributes) {    
            return [
                'dtr_time_in' => Carbon::parse($this->faker->dateTimeBetween('-1 week', 'now'))->toDateTimeString(),
                'dtr_time_in_image' => $this->generateImage(),
            ];
        });
    }    

    /**
     * Specify that only time in should be created.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withTimeOut(): self
    {
        return $this->state(function (array $attributes) {
            // Explicitly creating a Carbon instance from dtr_time_in
            $timeIn = Carbon::parse($attributes['dtr_time_in'] ?? now());
    
            // Add exactly 8 hours to dtr_time_in to get dtr_time_out
            $timeOut = $timeIn->copy()->addHours(8);
    
            return [
                'dtr_time_out' => $timeOut->toDateTimeString(),
                'dtr_time_out_image' => $this->generateImage(),
                'dtr_end_of_the_day_report' => $this->faker->paragraph,
            ];
        });
    }   

    /**
     * Indicate that the leave request a date from now onwards.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withLeaveRequest(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'dtr_absence_date' => Carbon::now()->addWeeks(2)->format('Y-m-d'),
                'dtr_absence_reason' => $this->faker->sentence(),
            ];
        });
    }
    
    /**
     * Indicate that the DTR has an approved absence with a date from now onwards.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withAbsenceApprovedAt(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'dtr_absence_approved_at' => Carbon::now()->addDays(rand(0, 14))->format('Y-m-d H:i:s'),
            ];
        });
    }

    /**
     * Generate a fake image URL with a specific extension.
     *
     * @return string|null
     */
    protected function generateImage(): ?string
    {
        // Generate a random image filename with an acceptable format
        $format = $this->faker->randomElement(['jpg', 'jpeg', 'png']);
        $filename = $this->faker->word . '.' . $format;

        // Simulate a public storage path or URL
        $fakeImageUrl = 'https://via.placeholder.com/640x480.png?text=' . $filename;

        // Return the fake image URL
        return $fakeImageUrl;
    }
}
