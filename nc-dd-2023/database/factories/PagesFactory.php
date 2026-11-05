<?php

namespace Database\Factories;

use App\Models\Pages;
use Illuminate\Database\Eloquent\Factories\Factory;

class PagesFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Pages::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'good' => rand(0, 1),
            'draft' => rand(0, 1),
            'title' => $this->faker->paragraph(1),
            'text' => $this->faker->paragraph(15),
            'alias' => $this->faker->unique()->lexify('page_???'),
            'created_id' => 1,
            'updated_id' => 1,
            'created_at' => $this->faker->dateTimeBetween('-1 years', 'now')->format('Y-m-d H:i:s'),
        ];
    }

}
