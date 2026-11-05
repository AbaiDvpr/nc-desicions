<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Удаление поля "type" в таблице companies-imports
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies-imports', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies-imports', function (Blueprint $table) {
            $table->string('type', 10)->after('companyUuid')->comment('Тип файла');
        });
    }
};
