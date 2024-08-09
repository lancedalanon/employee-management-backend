<?php

namespace Database\Factories;

use App\Models\Dtr;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Dtr>
 */
class LeaveRequestFactory extends Factory
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
            'user_id' => User::factory(),  // Placeholder, to be overridden
            'absence_date' => Carbon::tomorrow()->format('Y-m-d'),  // Placeholder, to be overridden
            'absence_reason' => $this->faker->sentence,
        ];
    }

    /**
     * Create multiple entries within a given date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @param int $userId
     * @return array
     */
    public static function dateRange(string $startDate, string $endDate, int $userId): array
    {
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        // Ensure the start date is before the end date
        if ($startDate->gt($endDate)) {
            throw new \InvalidArgumentException('Start date must be before or equal to end date.');
        }

        $dtrs = [];
        // Loop through the date range and create an entry for each date
        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            $dtrs[] = self::new()->create([
                'user_id' => $userId,
                'absence_date' => $date->format('Y-m-d'),
                'absence_reason' => self::new()->make()->absence_reason,
            ]);
        }

        return $dtrs;
    }
}
