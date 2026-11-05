<?php

namespace Database\Factories;

use App\Models\News;
use Illuminate\Database\Eloquent\Factories\Factory;

class NewsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = News::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'good'        => rand(0, 1),
            'draft'       => rand(0, 1),
            'title'       => $this->faker->paragraph(1),
            'short'       => $this->faker->paragraph(5),
            'full'        => $this->faker->paragraph(10),
            'category_id' => 1,
            'created_id'  => 1,
            'updated_id'  => 1,
            'created_at'  => $this->faker->dateTimeBetween('-1 years', 'now')->format('Y-m-d H:i:s'),
        ];
    }

}
