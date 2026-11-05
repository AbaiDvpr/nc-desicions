<?php declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабирка для наполнения тестовыми данными Компаний
 *
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
            'name'       => $this->faker->company,
            'userId'     => 2,
            'tariffId'   => 1,
            "currency"   => 'usd',
            'created_at' => now(),
        ];
    }
}
