<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция для работы с показателями полученными из Нацбанка
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
        Schema::create('indicators', function (Blueprint $table) {
            $table->id();
            $table->date('relevant')->nullable()->comment('На какой день показатели');
            $table->double('inflation', 8, 2)->nullable()->comment('Цель по инфляции');
            $table->double('annualInflation', 8, 2)->nullable()->comment('Годовая инфляция');
            $table->double('baseRate', 8, 2)->nullable()->comment('Базовая ставка');
            $table->double('tonia', 8, 2)->nullable()->comment('TONIA');
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
        Schema::dropIfExists('indicators');
    }
};
