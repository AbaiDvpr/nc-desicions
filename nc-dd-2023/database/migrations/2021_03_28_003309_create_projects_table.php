<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('uuid')->primary();

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('tariff_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('tariff_id')->references('id')->on('tariffs');

            $table->integer('correction')->default(0)->comment('Кол-во правок');
            $table->enum('status', ['new', 'edited', 'completed'])->default('new')->comment('Статус');
            $table->integer('step')->default(1)->comment('Шаг заполнения проекта');
            $table->string('name', 512)->nullable()->comment('Название проекта');
            $table->integer('form')->default(0)->comment('Организационно-правовая форма');
            $table->integer('tax_regime')->default(0)->comment('Режим налогообложения');
            $table->string('currency', 10)->nullable()->comment('В какой валюте производить расчет');
            $table->integer('time_param')->default(0)->comment('Временные параметры расчета');
            $table->integer('schedule')->default(0)->comment('График работы');
            $table->json('planning')->nullable()->comment('Период планирования');

            $table->boolean('own_investments')->default(0)->comment('Будете ли вы участвовать собственными вложениями?');

            $table->date('salesStartDate')->nullable()->comment('Дата начала продаж');
            $table->date('plannedVolume')->nullable()->comment('Срок выхода на плановый объем');

            $table->integer('otherIncome')->nullable()->comment('Прочие доходы (при наличии)');
            $table->integer('utilityCosts')->nullable()->comment('Коммунальные затраты при проектах в сфере производства');

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
        Schema::dropIfExists('projects');
    }
}
