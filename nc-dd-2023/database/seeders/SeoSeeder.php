<?php

namespace Database\Seeders;

use App\Models\Seo;
use Illuminate\Database\Seeder;

class SeoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Seo::create([
            'title' => env('APP_NAME'),
            'description' => env('APP_NAME'),
            'created_id' => 1,
            'updated_id' => 1
        ]);
    }
}
