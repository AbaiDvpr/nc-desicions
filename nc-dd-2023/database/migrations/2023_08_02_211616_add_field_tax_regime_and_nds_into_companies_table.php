<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция для добавления колонки "tax_regime" и "nds" в таблицу companies
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'tax_regime')) {
                $table->integer('tax_regime')->default(0)->after('form')->comment('Форма налогообложения');
            }

            if (!Schema::hasColumn('companies', 'nds')) {
                $table->boolean('nds')->default(false)->after('tax_regime')->comment('НДС');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('tax_regime');
            $table->dropColumn('nds');
        });
    }
};
