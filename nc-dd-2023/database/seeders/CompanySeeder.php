<?php declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Заполнение тестовыми данными таблицы с Компанями
 */
class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        Company::factory()->count(10)->create();
    }
}
