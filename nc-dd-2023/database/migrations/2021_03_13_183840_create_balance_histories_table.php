<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBalanceHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('balance-histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('tariff_id')->nullable();
            $table->foreign('tariff_id')->references('id')->on('tariffs');

            $table->enum('operation', ['refill', 'cut', 'refund'])->default('cut')->comment('Тип операции');
            $table->double('paid', 30, 2)->nullable()->comment('Сумма транзакции');
            $table->double('residue', 30, 2)->nullable()->comment('Остаток');
            $table->dateTime('paid_at')->comment('Дата проведения оплаты');
            $table->string('comment', 255)->nullable()->comment('Комментарий');

            $table->unsignedBigInteger('created_id')->comment('Тот кто выполнил операцию');
            $table->foreign('created_id')->references('id')->on('users');
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
        Schema::dropIfExists('balance-histories');
    }
}
