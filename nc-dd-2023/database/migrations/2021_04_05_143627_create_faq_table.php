<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFaqTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('faq', function (Blueprint $table) {
            $table->id();
            $table->boolean('main')->default(false)->comment('Вывод на главную');
            $table->boolean('published')->default(false)->comment('Опубликовано или нет');
            $table->string('lang')->nullable()->comment('Язык подачи вопроса');
            $table->string('fio')->nullable()->comment('ФИО');
            $table->string('email')->nullable()->comment('E-mail');
            $table->string('phone')->nullable()->comment('Телефон');
            $table->text('question')->nullable()->comment('Текст вопроса');
            $table->text('reply')->nullable()->comment('Текст ответа');
            $table->string('ip')->nullable()->comment('IP адрес с которого поступил вопрос');
            $table->integer('updated_id')->nullable()->comment('Обновил запись');
            $table->dateTime('published_at')->nullable()->comment('Время публикации вопроса с ответом');
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
        Schema::dropIfExists('faq');
    }
}
