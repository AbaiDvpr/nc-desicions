<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция для удаления колонки "version" из плана ОПУ, ДДС и Баланса
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
        Schema::table('companies-opu-reports-plan', function (Blueprint $table) {
            $table->dropColumn('version');
        });

        Schema::table('companies-dds-reports-plan', function (Blueprint $table) {
            $table->dropColumn('version');
        });

        Schema::table('companies-balance-reports-plan', function (Blueprint $table) {
            $table->dropColumn('version');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('companies-opu-reports-plan', function (Blueprint $table) {
            $table->integer('version')
                ->after('period')
                ->default(1)
                ->comment('Версия показателей');
        });

        Schema::table('companies-dds-reports-plan', function (Blueprint $table) {
            $table->integer('version')
                ->after('period')
                ->default(1)
                ->comment('Версия показателей');
        });

        Schema::table('companies-balance-reports-plan', function (Blueprint $table) {
            $table->integer('version')
                ->after('period')
                ->default(1)
                ->comment('Версия показателей');
        });
    }
};
