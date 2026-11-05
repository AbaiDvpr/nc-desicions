<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalaryFundTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects-salary-fund', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->foreign('uuid')->references('uuid')->on('projects')->cascadeOnDelete();

            $table->string('name', 255)->nullable()->comment('Наименование должности');
            $table->integer('count')->nullable()->comment('Количество');
            $table->integer('salaryPerMonth')->nullable()->comment('Оклад в месяц');

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
        Schema::dropIfExists('projects-salary-fund');
    }
}
