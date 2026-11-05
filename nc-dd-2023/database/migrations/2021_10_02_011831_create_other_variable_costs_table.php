<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOtherVariableCostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects-variable-costs-other', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->foreign('uuid')->references('uuid')->on('projects');

            $table->string('title', 255)->nullable()->comment('Наименование затрат');
            $table->integer('price')->nullable()->comment('Стоимость затрат в месяц');

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
        Schema::dropIfExists('projects-variable-costs-other');
    }
}
