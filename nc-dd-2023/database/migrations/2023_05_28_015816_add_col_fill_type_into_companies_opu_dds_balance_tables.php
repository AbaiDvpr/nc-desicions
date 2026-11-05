<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция для добавления колонки "fillType" для таблицы ОПУ, ДДС и Баланса
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
        Schema::table('companies-opu', function (Blueprint $table) {
            if (!Schema::hasColumn('companies-opu', 'fillType')) {
                $table->integer('fillType')
                    ->after('period')
                    ->default(0)
                    ->comment('Тип заполнения');
            }
        });

        Schema::table('companies-dds', function (Blueprint $table) {
            if (!Schema::hasColumn('companies-dds', 'fillType')) {
                $table->integer('fillType')
                    ->after('period')
                    ->default(0)
                    ->comment('Тип заполнения');
            }
        });

        Schema::table('companies-balance', function (Blueprint $table) {
            if (!Schema::hasColumn('companies-balance', 'fillType')) {
                $table->integer('fillType')
                    ->after('period')
                    ->default(0)
                    ->comment('Тип заполнения');
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
        Schema::table('companies-opu', function (Blueprint $table) {
            $table->dropColumn('fillType');
        });

        Schema::table('companies-dds', function (Blueprint $table) {
            $table->dropColumn('fillType');
        });

        Schema::table('companies-balance', function (Blueprint $table) {
            $table->dropColumn('fillType');
        });
    }
};
