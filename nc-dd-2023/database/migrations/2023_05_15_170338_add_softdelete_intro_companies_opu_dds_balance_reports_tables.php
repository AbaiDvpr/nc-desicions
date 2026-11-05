<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция для добавления softdelete в таблицы отчетов ОПУ, ДДС и Баланса
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
        Schema::table('companies-opu-reports', function (Blueprint $table) {
            if (!Schema::hasColumn('companies-opu-reports', 'deleted_at')) {
                $table->softDeletes()
                    ->after('updated_at')
                    ->comment('Дата удаления');
            }
        });

        Schema::table('companies-opu-reports-plan', function (Blueprint $table) {
            if (!Schema::hasColumn('companies-opu-reports-plan', 'deleted_at')) {
                $table->softDeletes()
                    ->after('updated_at')
                    ->comment('Дата удаления');
            }
        });

        Schema::table('companies-dds-reports', function (Blueprint $table) {
            if (!Schema::hasColumn('companies-dds-reports', 'deleted_at')) {
                $table->softDeletes()
                    ->after('updated_at')
                    ->comment('Дата удаления');
            }
        });

        Schema::table('companies-dds-reports-plan', function (Blueprint $table) {
            if (!Schema::hasColumn('companies-dds-reports-plan', 'deleted_at')) {
                $table->softDeletes()
                    ->after('updated_at')
                    ->comment('Дата удаления');
            }
        });

        Schema::table('companies-balance-reports', function (Blueprint $table) {
            if (!Schema::hasColumn('companies-balance-reports', 'deleted_at')) {
                $table->softDeletes()
                    ->after('updated_at')
                    ->comment('Дата удаления');
            }
        });

        Schema::table('companies-balance-reports-plan', function (Blueprint $table) {
            if (!Schema::hasColumn('companies-balance-reports-plan', 'deleted_at')) {
                $table->softDeletes()
                    ->after('updated_at')
                    ->comment('Дата удаления');
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
        Schema::table('companies-opu-reports', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('companies-opu-reports-plan', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('companies-dds-reports', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('companies-dds-reports-plan', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('companies-balance-reports', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('companies-balance-reports-plan', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
