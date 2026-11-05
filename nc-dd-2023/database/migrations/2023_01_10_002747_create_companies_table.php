<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция для работы с Компаниями
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->uuid('uuid')->primary();

            $table->unsignedBigInteger('userId');
            $table->unsignedBigInteger('tariffId');
            $table->foreign('userId')->references('id')->on('users');
            $table->foreign('tariffId')->references('id')->on('tariffs');

            $table->string('name')->comment('Название компании');
            $table->text('description', 2000)->nullable()->comment('Описание компании');
            $table->string('currency', 10)->default('kzt')->comment('В какой валюте производить расчет');

            $table->softDeletes()->comment('Дата удаления');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
