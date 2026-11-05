<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция для создания таблицы тарифы пользователя
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users-tariffs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('userId');
            $table->unsignedBigInteger('tariffId')->comment('ID оригинального тарифа');
            $table->foreign('userId')->references('id')->on('users');
            $table->foreign('tariffId')->references('id')->on('tariffs');

            $table->integer('type')->nullable()->comment('Тип тарифа');
            $table->json('name')->nullable()->comment('Название тарифа');
            $table->double('price', 20, 2)->nullable()->comment('Цена в тенге');
            $table->json('description')->nullable()->comment('Описание тарифа');
            $table->json('company')->nullable()->comment('Ограничения для компаний');
            $table->json('project')->nullable()->comment('Ограничения для проектов');
            $table->dateTime('expire')->nullable()->comment('Дата истечения срока тарифного плана');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users-tariffs');
    }
};
