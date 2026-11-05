<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');

            $table->integer('serviceType')->nullable()->comment('Идентификатор сервиса в платежной системе');
            $table->string('service')->comment('Имя услуги/тарифа');
            $table->float('amount')->comment('Сумма операции');
            $table->string('description', 255)->nullable()->comment('Комментарий');
            $table->string('email')->comment('E-mail для счёта');
            $table->string('phone')->comment('Номер телефона');
            $table->string('operationId')->nullable()->comment('ID операции в платежной системе');

            // По дате оплаты понимаем что счёт был оплачен
            $table->dateTime('paid_at')->nullable()->comment('Дата проведения оплаты');
            $table->dateTime('deathDate')->comment('Срок действия счета');
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
        Schema::dropIfExists('invoices');
    }
};
