<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Project\AdministrativeExpenses;

/**
 * Миграция для добавления поля "type" в таблицу Административные расходы
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects-administrative-expenses', function (Blueprint $table) {
            $table->integer('type')
                ->default(AdministrativeExpenses::TYPE_OTHER)
                ->after('invest')
                ->comment('Тип расхода');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects-administrative-expenses', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
