<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция для добавления колонки "seasonal" в таблицу projects-volume-sales
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
        Schema::table('projects-volume-sales', function (Blueprint $table) {
            if (!Schema::hasColumn('projects-volume-sales', 'seasonal')) {
                $table->json('seasonal')
                    ->after('period')
                    ->nullable()
                    ->comment('Сезонность');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('projects-volume-sales', function (Blueprint $table) {
            $table->dropColumn('seasonal');
        });
    }
};
