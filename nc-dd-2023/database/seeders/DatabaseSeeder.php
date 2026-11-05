<?php

namespace Database\Seeders;

use App\Models\Categories;
use App\Models\Faq;
use App\Models\News;
use App\Models\Pages;
use App\Models\Tariff;
use App\Models\User;
use Database\Factories\FaqFactory;
use Database\Factories\TariffFactory;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        (new RolesAndPermissionsSeeder())->run();
        (new SeoSeeder())->run();

        $admin = User::create([
            'name' => 'Administrator',
            'admin' => true,
            'email' => 'ncdecision@gmail.com',
            'email_verified_at' => now(),
            'password' => 'password', // '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
        ]);
        $admin->refresh()->assignRole('administrator');

        $client = User::create([
            'name' => 'Я - клиент',
            'admin' => false,
            'email' => 'client@nc.com',
            'email_verified_at' => now(),
            'password' => 'password', // '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
        ]);
        $client->refresh()->assignRole('client');

        Tariff::factory(1)->create();
        Faq::factory(5)->create();

//        User::factory(50)->afterCreating(function ($user) {
//            return $user->assignRole('client');
//        })->create();
//
//        Categories::create([
//            'good'        => true,
//            'draft'       => false,
//            'title'       => 'Категория',
//            'description' => 'Описание категории',
//            'created_id'  => 1,
//            'updated_id'  => 1,
//            'slug'        => 'category'
//        ]);
//
//        News::factory(10)->create();
//
//        Pages::factory(5)->create();
    }
}
