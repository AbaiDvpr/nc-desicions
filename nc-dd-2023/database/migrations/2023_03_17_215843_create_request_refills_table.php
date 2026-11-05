<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Создание таблицы для хранения заявок на пополнение счёта через Kaspi
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
        Schema::create('request-refills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('userId');
            $table->foreign('userId')->references('id')->on('users');
            $table->boolean('isPaid')->default(false)->comment('Оплачено/Не оплачено');

            $table->string('fullName', 100)->comment('ФИО');
            $table->string('phone', 30)->comment('Телефон');
            $table->string('comment', 200)->nullable()->comment('Комментарий');

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('request-refills');
    }
};
