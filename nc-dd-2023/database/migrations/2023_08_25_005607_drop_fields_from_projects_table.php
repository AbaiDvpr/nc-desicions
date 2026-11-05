<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция для удаления полей в таблице фин моделей
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('tariff_id');
            $table->dropColumn('correction');
            $table->dropColumn('maxCorrection');
        });
    }
};
