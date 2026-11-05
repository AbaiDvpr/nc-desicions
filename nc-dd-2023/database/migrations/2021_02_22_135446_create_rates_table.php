<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rates', function (Blueprint $table) {
            $table->id();
            $table->date('relevant')->nullable()->comment('На какой день курс');
            foreach (config('rates.supportedRates') as $rates){
                $table->double('amount_' . $rates, 8, 2)->nullable()->comment('Стоимость');
                $table->string('unit_' . $rates)->nullable()->comment('Количество единиц');
            }
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
        Schema::dropIfExists('rates');
    }
}
