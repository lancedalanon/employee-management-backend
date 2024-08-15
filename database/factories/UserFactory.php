<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $suffixes = ['Jr.', 'Sr.', 'III', 'IV', 'V'];
        $gender = ['Male', 'Female'];

        return [
            'first_name' => $this->faker->firstName,
            'middle_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'suffix' => $this->faker->randomElement(array_merge($suffixes, [null])),
            'place_of_birth' => $this->faker->city,
            'date_of_birth' => $this->faker->date(),
            'gender' => $this->faker->randomElement($gender),
            'username' => $this->faker->userName,
            'email' => $this->faker->unique()->safeEmail,
            'phone_number' => $this->faker->regexify('09[0-9]{2}-[0-9]{3}-[0-9]{4}'),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'recovery_email' => $this->faker->unique()->safeEmail,
            'emergency_contact_name' => $this->faker->firstName,
            'emergency_contact_number' => $this->faker->regexify('09[0-9]{2}-[0-9]{3}-[0-9]{4}'),
            'remember_token' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
