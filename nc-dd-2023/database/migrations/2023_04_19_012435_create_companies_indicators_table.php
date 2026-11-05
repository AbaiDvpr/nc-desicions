<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция для работы с показателями компаний
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up():void
    {
        Schema::create('companies-indicators', function (Blueprint $table) {
            $table->id();
            $table->uuid('companyUuid');
            $table->foreign('companyUuid')->references('uuid')->on('companies')->cascadeOnDelete();

            $table->double('proportionVariableCosts', 8, 3)
                ->default(0)
                ->comment('Доля переменных затрат');

            $table->double('longTermAssets', 8, 3)
                ->default(0)
                ->comment('Потребность в долгосрочных активах на тенге выручки');

            $table->double('workingCapital', 8, 3)
                ->default(0)
                ->comment('Потребность в оборотном капитале на тенге выручки');

            $table->double('needCash', 8, 3)
                ->default(0)
                ->comment('Потребность в денежных средства на тенге выручки');

            $table->double('interestRateOnLoans', 8, 3)
                ->default(0)
                ->comment('Процентная ставка по кредитам (в % от объема кредита на конец периода)');

            $table->double('incomeTaxRate', 8, 3)
                ->default(0)
                ->comment('Ставка налога на прибыль');

            $table->double('dividendPayoutRatio', 8, 3)
                ->default(0)
                ->comment('Коэффициент дивидендных выплат');

            $table->double('inflationIndex', 8, 3)
                ->default(0)
                ->comment('Индекс инфляции (темп прироста постоянных расходов)');

            $table->double('revenueGrowthRate', 8, 3)
                ->default(0)
                ->comment('Темп прироста выручки (REV/REV (-1) - 1)');

            $table->double('loanVolume', 8, 3)
                ->default(0)
                ->comment('Объем привлекаемого/погашаемого кредита в n периоде');

            $table->double('acquisitionAssets', 20, 3)
                ->default(0)
                ->comment('Приобретение активов');

            $table->softDeletes()->comment('Дата удаления');

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down():void
    {
        Schema::dropIfExists('companies-indicators');
    }
};
