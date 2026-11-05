<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttractingInvestmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects-attracting-investments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->foreign('uuid')->references('uuid')->on('projects')->cascadeOnDelete();

            $table->integer('investment')->nullable()->comment('Условия привлечения инвестиции');
            $table->integer('investorTerm')->nullable()->comment('Срок участия инвестора в проекте');
            $table->double('investorDividend', 10, 2)->nullable()->comment('% дивидендов инвестора');
            $table->integer('loanTerm')->nullable()->comment('Срок займа');
            $table->integer('gracePeriod')->nullable()->comment('Льготный период');
            $table->double('annualRate', 10, 2)->nullable()->comment('Годовая ставка вознаграждения');

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
        Schema::dropIfExists('projects-attracting-investments');
    }
}
