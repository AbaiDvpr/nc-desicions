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
        Schema::table('tariffs', function (Blueprint $table) {
            $table->integer('correction')->after('price')->nullable()->comment('Кол-во корректировок');
            $table->integer('maxProjects')->after('correction')->nullable()->comment('Максимальное кол-во проектов');
        });

        // Добавляем колонку с максимальным кол-вом возможных корректировок
        // В таблицу с проектом добавляем для того, чтоб если человек купил новый тарифный план,
        // то не открывалась возможность корректировать старый или начатые проекты
        Schema::table('projects', function (Blueprint $table) {
            $table->integer('maxCorrection')->after('correction')
                ->default(2)->comment('Максимальное кол-во корректировок');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dateTime('tariff_expire')->after('balance')->nullable()
                ->comment('Дата истечения срока тарифного плана');
            $table->unsignedBigInteger('tariff_id')->after('balance')->nullable()
                ->comment('ID купленного тарифа');
            $table->foreign('tariff_id')->references('id')->on('tariffs');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            $table->dropColumn('correction');
        });

        // Удаляем колонку с максимальным кол-вом возможных корректировок
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('maxCorrection');
        });

        // Удаляем колонку с максимальным кол-вом возможных корректировок
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('tariff_expire');
            $table->dropForeign('users_tariff_id_foreign');
            $table->dropColumn('tariff_id');
        });
    }
};
