<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->boolean('good')->default(false)->comment('Включен/Выключен раздел');
            $table->boolean('draft')->default(true)->comment('Черновик');
            $table->json('title')->nullable()->comment('Название страницы');
            $table->json('text')->nullable()->comment('Текст страницы');
            $table->string('alias', 100)->nullable()->unique()->comment('Алиас страницы');
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
        Schema::dropIfExists('pages');
    }
}
