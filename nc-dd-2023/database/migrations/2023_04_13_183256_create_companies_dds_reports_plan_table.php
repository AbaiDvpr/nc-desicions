<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция для работы с отчетами о прибыли в ДДС
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
        Schema::create('companies-dds-reports-plan', function (Blueprint $table) {
            $table->id();
            $table->uuid('companyUuid');
            $table->foreign('companyUuid')->references('uuid')->on('companies')->cascadeOnDelete();

            $table->date('period')->comment('Период');

            $table->integer('version')->default(1)->comment('Версия показателей');

            $table->decimal('netProfit', 30, 5)
                ->nullable()
                ->comment('Чистая прибыль');

            $table->decimal('increaseInNetWorkingCapital', 30, 5)
                ->nullable()
                ->comment('Прирост чистого оборотного капитала');

            $table->decimal('cashFlowsFromOperatingActivities', 30, 5)
                ->nullable()
                ->comment('Денежные потоки от операционной деятельности');

            $table->decimal('investments', 30, 5)
                ->nullable()
                ->comment('Инвестиции');

            $table->decimal('cashFlowsFromInvestingActivities', 30, 5)
                ->nullable()
                ->comment('Денежные потоки от инвестиционной деятельности');

            $table->decimal('receiptOrRepaymentOfFinancialLiabilities', 30, 5)
                ->nullable()
                ->comment('Поступление/погашение финансовых обязательств');

            $table->decimal('issueOfShares', 30, 5)
                ->nullable()
                ->comment('Эмиссия акций');

            $table->decimal('cashFlowsFromFinancingActivities', 30, 5)
                ->nullable()
                ->comment('Денежные потоки от финансовой деятельности');

            $table->decimal('totalChangeInCash', 30, 5)
                ->nullable()
                ->comment('Итого изменение денежных средств');

            $table->decimal('impactOfExchangeRatesAgainstTenge', 30, 5)
                ->nullable()
                ->comment('Влияние обменных курсов валют к тенге');

            $table->decimal('cashAtBeginningPeriod', 30, 5)
                ->nullable()
                ->comment('Денежные средства на начало периода');

            $table->decimal('cashAtEndPeriod', 30, 5)
                ->nullable()
                ->comment('Денежные средства на конец периода');

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
        Schema::dropIfExists('companies-dds-reports-plan');
    }
};
