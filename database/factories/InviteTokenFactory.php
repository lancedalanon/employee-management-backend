<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\InviteToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Carbon\Carbon;

class InviteTokenFactory extends Factory
{
    protected $model = InviteToken::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'email' => $this->faker->unique()->safeEmail,
            'token' => Str::random(60),
            'expires_at' => Carbon::now()->addDays(7),
            'used_at' => null,
        ];
    }
}
