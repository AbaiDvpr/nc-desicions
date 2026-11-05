<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'admin' => 0,
            'email' => $this->faker->unique()->safeEmail,
            'mobile' => $this->getRandomMobileNumber(),
            'email_verified_at' => now(),
            'balance' => rand(1000, 500000),
            'created_at' => $this->faker->dateTimeBetween('-5 years', 'now')->format('Y-m-d H:i:s'),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * @return string
     */
    protected function getRandomMobileNumber(): string
    {
        return '+7 (700) ' . $this->faker->numberBetween(100, 999) . '-' . $this->faker->numberBetween(10, 99) . '-' . $this->faker->numberBetween(10, 99);
    }
}
