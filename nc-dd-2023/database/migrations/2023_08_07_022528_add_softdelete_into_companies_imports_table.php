<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция для добавления softdelete в таблицу companies-imports
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies-imports', function (Blueprint $table) {
            if (!Schema::hasColumn('companies-imports', 'deleted_at')) {
                $table->softDeletes()
                    ->after('updated_at')
                    ->comment('Дата удаления');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies-imports', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
