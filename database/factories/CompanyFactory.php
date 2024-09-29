<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Company;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Company::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company_name' => $this->faker->company(),
            'company_registration_number' => $this->faker->unique()->numerify('##########'),
            'company_tax_id' => $this->faker->unique()->numerify('###-##-####'),
            'company_address' => $this->faker->address(),
            'company_city' => $this->faker->city(),
            'company_state' => $this->faker->state(),
            'company_postal_code' => $this->faker->postcode(),
            'company_country' => $this->faker->country(),
            'company_phone_number' => $this->faker->phoneNumber(),
            'company_email' => $this->faker->companyEmail(),
            'company_website' => $this->faker->url(),
            'company_industry' => $this->faker->word(),
            'company_founded_at' => $this->faker->date(),
            'company_description' => $this->faker->paragraph(),
            'company_full_time_start_time' => null,
            'company_part_time_end_time' => null,
            'company_full_time_start_time' => null,
            'company_part_time_end_time' => null,
            'deactivated_at' => null,
        ];
    }
}
