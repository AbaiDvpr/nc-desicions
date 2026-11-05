<?php

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

class FaqFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Faq::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        $langs = ['ru', 'kk', 'en'];

        return [
            'main'         => rand(0, 1),
            'published'    => rand(0, 1),
            'lang'         => $langs[array_rand($langs, 1)],
            'fio'          => $this->faker->name,
            'email'        => $this->faker->unique()->safeEmail,
            'phone'        => $this->getRandomMobileNumber(),
            'question'     => $this->faker->paragraph(5),
            'reply'        => $this->faker->paragraph(10),
            'published_at' => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d H:i:s'),
            'created_at'   => $this->faker->dateTimeBetween('-5 years', 'now')->format('Y-m-d H:i:s'),
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
