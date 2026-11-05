<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSeoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('seo', function (Blueprint $table) {
            $table->id();
            $table->json('title')->nullable()->comment('Заголовок сайта');
            $table->json('description')->nullable()->comment('Описание сайта');
            $table->json('keywords')->nullable()->comment('Ключевые слова');
            $table->integer('created_id')->nullable()->comment('Создал запись');
            $table->integer('updated_id')->nullable()->comment('Обновил запись');
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
        Schema::dropIfExists('seo');
    }
}
