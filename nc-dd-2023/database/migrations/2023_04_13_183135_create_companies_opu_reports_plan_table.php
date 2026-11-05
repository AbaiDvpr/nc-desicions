<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция для работы планами ОПУ
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
        Schema::create('companies-opu-reports-plan', function (Blueprint $table) {
            $table->id();
            $table->uuid('companyUuid');
            $table->foreign('companyUuid')->references('uuid')->on('companies')->cascadeOnDelete();

            $table->date('period')->comment('Период');

            $table->integer('version')->default(1)->comment('Версия показателей');

            $table->decimal('income', 30, 5)
                ->nullable()
                ->comment('Выручка от реализации продукции');

            $table->decimal('costPrice', 30, 5)
                ->nullable()
                ->comment('Себестоимость');

            $table->decimal('generalAndAdminExpenses', 30, 5)
                ->nullable()
                ->comment('ОиАР');

            $table->decimal('profitBeforeTaxesAndPercents', 30, 5)
                ->nullable()
                ->comment('Прибыль до налогов и процентов');

            $table->decimal('financialIncomeOrExpenses', 30, 5)
                ->nullable()
                ->comment('Финансовые доходы/расходы');

            $table->decimal('otherIncomeOrExpenses', 30, 5)
                ->nullable()
                ->comment('Прочие доходы/расходы');

            $table->decimal('exchangeDifference', 30, 5)
                ->nullable()
                ->comment('Курсовая разница');

            $table->decimal('profitBeforeTaxes', 30, 5)
                ->nullable()
                ->comment('Прибыль до налогов');

            $table->decimal('incomeTax', 30, 5)
                ->nullable()
                ->comment('Налог на прибыль');

            $table->decimal('netProfit', 30, 5)
                ->nullable()
                ->comment('Чистая прибыль');

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('companies-opu-reports-plan');
    }
};
