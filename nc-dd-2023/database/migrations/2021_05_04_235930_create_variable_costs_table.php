<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVariableCostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects-variable-costs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->foreign('uuid')->references('uuid')->on('projects');
            $table->unsignedBigInteger('vs_id')->nullable()->comment('ID продукта из таблицы План продаж');
            $table->foreign('vs_id')->references('id')->on('projects-volume-sales');

            $table->string('costType', 255)->nullable()->comment('Вид затрат');
            $table->integer('price')->nullable()->comment('Стоимость затрат');

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
        Schema::dropIfExists('projects-variable-costs');
    }
}
