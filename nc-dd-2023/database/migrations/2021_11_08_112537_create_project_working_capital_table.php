<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectWorkingCapitalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects-working-capital', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->foreign('uuid')->references('uuid')->on('projects')->cascadeOnDelete();

            $table->integer('replenishPeriod')->nullable()->comment('Общий срок пополнения');
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
        Schema::dropIfExists('projects-working-capital');
    }
}
