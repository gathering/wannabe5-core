<?php

namespace Database\Factories;

use App\Enums\UserProfileGender;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserProfile>
 */
class UserProfileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = UserProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'firstname' => fake()->firstname(),
            'lastname' => fake()->lastname(),
            'nickname' => fake()->username(),
            'gender' => fake()->randomElement(UserProfileGender::cases())->value,
            'birthdate' => fake()->date('Y-m-d'),
            'phone' => fake()->e164PhoneNumber(),
            'streetaddress' => fake()->streetAddress(),
            'postcode' => fake()->postcode(),
            'town' => fake()->city(),
            'countrycode' => 'US', // Faker is using US data
        ];
    }
}
