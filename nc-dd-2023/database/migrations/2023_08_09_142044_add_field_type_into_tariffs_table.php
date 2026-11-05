<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Tariff;

/**
 * Миграция для добавления колонки type для таблицы тарифов
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            if (!Schema::hasColumn('tariffs', 'type')) {
                $table->integer('type')
                    ->default(Tariff::TYPE_MONTHLY)
                    ->after('default')
                    ->comment('Тип тарифа');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
