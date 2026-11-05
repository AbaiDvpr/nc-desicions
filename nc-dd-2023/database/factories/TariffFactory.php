<?php

namespace Database\Factories;

use App\Models\Tariff;
use Illuminate\Database\Eloquent\Factories\Factory;

class TariffFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Tariff::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'good' => true,
            'draft' => false,
            'name' => ['ru' => $this->faker->paragraph(5)],
            'price' => 15000,
            'created_at' => now()
        ];
    }
}
