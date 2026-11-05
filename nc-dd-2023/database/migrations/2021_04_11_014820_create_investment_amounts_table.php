<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvestmentAmountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Будете ли вы участвовать собственными вложениями?
        Schema::create('projects-investment-amounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->foreign('uuid')->references('uuid')->on('projects')->cascadeOnDelete();

            $table->integer('smr')->nullable()->comment('СМР');
            $table->integer('overhaul')->nullable()->comment('Капитальный ремонт');
            $table->integer('readyPremises')->nullable()->comment('Готовые помещения');
            $table->integer('equipment')->nullable()->comment('Приобретение оборудования');
            $table->integer('raw')->nullable()->comment('Закуп сырья');
            $table->integer('advertising')->nullable()->comment('Расходы на рекламу');
            $table->integer('wages')->nullable()->comment('Расходы по выплате заработной платы');
            $table->integer('rentalExpenses')->nullable()->comment('Расходы по аренде');
            $table->integer('otherExpenses')->nullable()->comment('Прочие расходы');

            $table->softDeletes()->comment('Дата удаления');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('projects-investment-amounts');
    }
}
