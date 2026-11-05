<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Добавляет поле для даты рождения и обновляет поле с номером телефона
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
        Schema::table('users', function (Blueprint $table) {
            $table->date('birthday')->nullable()
                ->after('mobile')
                ->comment('Дата рождения');

            $table->index('mobile', 'IDX_mobile');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('birthday');

            $table->dropIndex('IDX_mobile');
        });

    }
};
