<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция для добавления поля annuity в таблицу projects-attracting-investments
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects-attracting-investments', function (Blueprint $table) {
            $table->boolean('annuity')
                ->default(false)
                ->after('annualRate')
                ->comment('Аннуитетный расчет');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects-attracting-investments', function (Blueprint $table) {
            $table->dropColumn('annuity');
        });
    }
};
