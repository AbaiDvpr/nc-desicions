<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReadyPremisesListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects-ready-premises-lists', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->foreign('uuid')->references('uuid')->on('projects')->cascadeOnDelete();

            $table->string('name', 255)->nullable()->comment('Наименование');
            $table->integer('count')->nullable()->comment('Кол-во');
            $table->integer('priceOneUnit')->nullable()->comment('Цена за ед');
            $table->integer('price')->nullable()->comment('Стоимость');
            $table->integer('year')->nullable()->comment('Год приобретения');
            $table->integer('lifeTime')->nullable()->comment('Срок службы в годах');

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
        Schema::dropIfExists('projects-ready-premises-lists');
    }
}
