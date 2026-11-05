<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTariffsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tariffs', function (Blueprint $table) {
            $table->id();
            $table->boolean('good')->default(false)->comment('Вкл/Выкл');
            $table->boolean('draft')->default(true)->comment('Черновик');
            $table->json('name')->nullable()->comment('Название тарифа');
            $table->json('description')->nullable()->comment('Описание тарифа');
            $table->double('price', 20, 2)->nullable()->comment('Цена в тенге');
            $table->integer('created_id')->nullable()->comment('Создал запись');
            $table->integer('updated_id')->nullable()->comment('Обновил запись');
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
        Schema::dropIfExists('tariffs');
    }
}
