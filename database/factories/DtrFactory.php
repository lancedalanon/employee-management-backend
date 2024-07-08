<?php

namespace Database\Factories;

use App\Models\Dtr;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

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
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'time_in' => Carbon::now(),
            'time_out' => null,
        ];
    }

    /**
     * Set the time_in value.
     *
     * @param Carbon $timeIn
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withTimeIn(Carbon $timeIn)
    {
        return $this->state([
            'time_in' => $timeIn,
        ]);
    }

    /**
     * Set the time_out value.
     *
     * @param Carbon $timeOut
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withTimeOut(Carbon $timeOut)
    {
        return $this->state([
            'time_out' => $timeOut,
        ]);
    }
}
