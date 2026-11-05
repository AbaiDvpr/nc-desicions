<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Удаление поля fio и добавление полей name и surname в таблицу FAQ
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('faq', function (Blueprint $table) {
            $table->dropColumn('fio');

            if (!Schema::hasColumn('faq', 'name')) {
                $table->string('name')->after('lang')->nullable()->comment('Имя');
            }

            if (!Schema::hasColumn('faq', 'surname')) {
                $table->string('surname')->after('name')->nullable()->comment('Фамилия');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('faq', function (Blueprint $table) {
            $table->string('fio')->after('lang')->nullable()->comment('ФИО');

            $table->dropColumn('name');
            $table->dropColumn('surname');
        });
    }
};
