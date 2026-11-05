<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция для работы планами Баланса
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
        Schema::create('companies-balance-reports-plan', function (Blueprint $table) {
            $table->id();
            $table->uuid('companyUuid');
            $table->foreign('companyUuid')->references('uuid')->on('companies')->cascadeOnDelete();

            $table->date('period')->comment('Период');

            $table->integer('version')->default(1)->comment('Версия показателей');

            $table->decimal('longTermAssets', 30, 5)
                ->nullable()
                ->comment('Долгосрочные активы');

            $table->decimal('netWorkingCapital', 30, 5)
                ->nullable()
                ->comment('Чистый оборотный капитал');

            $table->decimal('cash', 30, 5)
                ->nullable()
                ->comment('Денежные средства');

            $table->decimal('stocks', 30, 5)
                ->nullable()
                ->comment('Запасы');

            $table->decimal('otherAssets', 30, 5)
                ->nullable()
                ->comment('Прочие активы');

            $table->decimal('totalAssets', 30, 5)
                ->nullable()
                ->comment('Итого активы');

            $table->decimal('authorizedCapital', 30, 5)
                ->nullable()
                ->comment('Уставный капитал');

            $table->decimal('retainedEarnings', 30, 5)
                ->nullable()
                ->comment('Нераспределенная прибыль');

            $table->decimal('creditsAndLoans', 30, 5)
                ->nullable()
                ->comment('Кредиты и займы');

            $table->decimal('shortTermLiabilities', 30, 5)
                ->nullable()
                ->comment('Краткосрочные обязательства');

            $table->decimal('otherLiabilities', 30, 5)
                ->nullable()
                ->comment('Прочие обязательства');

            $table->decimal('totalLiabilities', 30, 5)
                ->nullable()
                ->comment('Итого пассивы');

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
        Schema::dropIfExists('companies-balance-reports-plan');
    }
};
