<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция для удаления связи с тарифом и поля "tariff_expire" из таблицы users
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_tariff_id_foreign');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('tariff_expire');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign('projects_tariff_id_foreign');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign('companies_tariffid_foreign');
        });
    }
};
