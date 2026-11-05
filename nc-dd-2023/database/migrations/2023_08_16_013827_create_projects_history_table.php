<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция для создания таблицы история фин моделей
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projects-history', function (Blueprint $table) {
            $table->id();

            $table->uuid();
            $table->unsignedBigInteger('tariffId')->comment('ID тарифа');
            $table->unsignedBigInteger('userTariffId')->comment('ID пользовательского тарифа');

            $table->foreign('uuid')->references('uuid')->on('projects');
            $table->foreign('tariffId')->references('id')->on('tariffs');
            $table->foreign('userTariffId')->references('id')->on('users-tariffs');

            $table->integer('correction')->default(0)->comment('Кол-во правок');
            $table->timestamps();
            $table->softDeletes()->comment('Дата удаления');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects-history');
    }
};
