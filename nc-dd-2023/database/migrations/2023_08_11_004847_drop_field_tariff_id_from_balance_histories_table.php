<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция для удаления колонки "tariff_id" из balance-histories
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('balance-histories', function (Blueprint $table) {
            $table->dropForeign('balance_histories_tariff_id_foreign');
        });

        Schema::table('balance-histories', function (Blueprint $table) {
            $table->dropColumn('tariff_id');
        });
    }
};
