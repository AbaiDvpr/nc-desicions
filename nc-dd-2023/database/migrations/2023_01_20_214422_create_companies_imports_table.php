<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция для работы с импортируемыми файлами компании
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
        Schema::create('companies-imports', function (Blueprint $table) {
            $table->id();

            $table->uuid('companyUuid');
            $table->string('type', 10)->comment('Тип файла');
            $table->string('path')->comment('Путь к файлу');
            $table->boolean('status')->default(false)->comment('Статус загрузки файла');
            $table->string('ip')->comment('IP пользователя');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('companies-imports');
    }
};
