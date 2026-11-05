<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция для добавления колонки "indicatorId" в план ОПУ, ДДС и Баланса
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
            if (!Schema::hasColumn('companies-opu-reports-plan', 'indicatorId')) {
                $table->unsignedBigInteger('indicatorId')
                    ->after('companyUuid')
                    ->comment('ID показателя');

                $table->foreign('indicatorId')->references('id')->on('companies-indicators');
            }
        });

        Schema::table('companies-dds-reports-plan', function (Blueprint $table) {
            if (!Schema::hasColumn('companies-dds-reports-plan', 'indicatorId')) {
                $table->unsignedBigInteger('indicatorId')
                    ->after('companyUuid')
                    ->comment('ID показателя');

                $table->foreign('indicatorId')->references('id')->on('companies-indicators');
            }
        });

        Schema::table('companies-balance-reports-plan', function (Blueprint $table) {
            if (!Schema::hasColumn('companies-balance-reports-plan', 'indicatorId')) {
                $table->unsignedBigInteger('indicatorId')
                    ->after('companyUuid')
                    ->comment('ID показателя');

                $table->foreign('indicatorId')->references('id')->on('companies-indicators');
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
        Schema::table('companies-opu-reports-plan', function (Blueprint $table) {
            $table->dropColumn('indicatorId');
        });

        Schema::table('companies-dds-reports-plan', function (Blueprint $table) {
            $table->dropColumn('indicatorId');
        });

        Schema::table('companies-balance-reports-plan', function (Blueprint $table) {
            $table->dropColumn('indicatorId');
        });
    }
};
