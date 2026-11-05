<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Добавляем период продаж в Объем продаж
        Schema::table('projects-volume-sales', function (Blueprint $table) {
            $table->integer('period')->default(1)->after('price')->comment('Период продаж');
        });

        // Добавляем период затрат и флаг "На инвест период" в Переменные расходы
        Schema::table('projects-variable-costs-other', function (Blueprint $table) {
            $table->boolean('invest')->default(false)
                ->after('uuid')->comment('Затраты на инвест период');
            $table->integer('period')->default(1)->after('price')->comment('Период затрат');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
