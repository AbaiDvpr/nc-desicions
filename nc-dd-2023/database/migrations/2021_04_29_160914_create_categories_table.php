<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->boolean('good')->default(false)->comment('вкл\выкл');
            $table->boolean('draft')->default(true)->comment('Черновик');
            $table->json('title')->nullable()->comment('Название категории');
            $table->json('description')->nullable()->comment('Описание категории');
            $table->integer('created_id')->nullable()->comment('Создал категорию');
            $table->integer('updated_id')->nullable()->comment('Обновил категорию');
            $table->string('slug', 100)->nullable()->unique()->comment('Алиас категории');
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
        Schema::dropIfExists('categories');
    }
}
