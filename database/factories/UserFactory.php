<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Company;
use Spatie\Permission\Models\Role;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
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
        return [
            'first_name' => $this->faker->firstName(),
            'middle_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'place_of_birth' => $this->faker->city(),
            'date_of_birth' => $this->faker->date('Y-m-d', '2002-01-01'), 
            'gender' => $this->faker->randomElement(['Male', 'Female']),
            'username' => $this->faker->unique()->userName(),
            'email' => $this->faker->unique()->safeEmail(),
            'recovery_email' => $this->faker->unique()->safeEmail(),
            'emergency_contact_name' => $this->faker->name(),
            'emergency_contact_number' => $this->generateFormattedPhoneNumber(),
            'phone_number' => $this->generateFormattedPhoneNumber(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'company_id' => null,
        ];
    }

    /**
     * Define a state with roles assigned.
     *
     * @param array<string> $roles
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withRoles(array $roles = []): Factory
    {
        return $this->afterCreating(function (User $user) use ($roles) {
            // Create roles if they don't exist
            $availableRoles = [
                'employee',
                'intern',
                'full_time',
                'part_time',
                'day_shift',
                'afternoon_shift',
                'evening_shift',
                'early_shift',
                'night_shift',
            ];

            foreach ($availableRoles as $role) {
                Role::firstOrCreate(['name' => $role]);
            }

            // Assign the provided roles to the user
            if (empty($roles)) {
                // Default roles if none provided
                $shiftRoles = Role::whereIn('name', ['day_shift', 'afternoon_shift', 'evening_shift', 'early_shift', 'night_shift'])->get();
                $jobTypeRoles = Role::whereIn('name', ['full_time', 'part_time'])->get();
                $internRole = Role::where('name', 'intern')->first();

                $user->assignRole($shiftRoles->random());
                $user->assignRole($jobTypeRoles->random());
                $user->assignRole($internRole);
            } else {
                // Assign specified roles
                $rolesToAssign = Role::whereIn('name', $roles)->get();
                foreach ($rolesToAssign as $role) {
                    $user->assignRole($role);
                }
            }
        });
    }

    /**
     * Generate a phone number in the format 09XX-XXX-XXXX.
     *
     * @return string
     */
    protected function generateFormattedPhoneNumber(): string
    {
        // Generate a phone number in the format 09XX-XXX-XXXX
        $areaCode = $this->faker->numerify('09##'); // Generates 09XX
        $middlePart = $this->faker->numerify('###'); // Generates XXX
        $lastPart = $this->faker->numerify('####');  // Generates XXXX

        return "{$areaCode}-{$middlePart}-{$lastPart}";
    }
}
