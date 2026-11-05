<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->boolean('good')->default(false)->comment('вкл\выкл');
            $table->boolean('draft')->default(true)->comment('Черновик');
            $table->json('title')->nullable()->comment('Название новости');
            $table->json('short')->nullable()->comment('Короткое описание Новости');
            $table->json('full')->nullable()->comment('Полное описание Новости');
            $table->integer('category_id')->nullable()->comment('Выбранная категория');
            $table->integer('created_id')->nullable()->comment('Создал Новость');
            $table->integer('updated_id')->nullable()->comment('Обновил Новость');
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
        Schema::dropIfExists('news');
    }
}
