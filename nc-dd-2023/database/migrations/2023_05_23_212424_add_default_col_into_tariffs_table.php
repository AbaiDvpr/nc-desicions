<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Добавляет поле "default" в таблицу с тарифами
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            if (!Schema::hasColumn('tariffs', 'default')) {
                $table->boolean('default')
                    ->default(false)
                    ->after('draft')
                    ->comment('Тариф по умолчанию');
            }

            if (!Schema::hasColumn('tariffs', 'company')) {
                $table->json('company')
                    ->nullable()
                    ->after('description')
                    ->comment('Ограничения для компаний');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            $table->dropColumn('default');
            $table->dropColumn('company');
        });
    }
};
