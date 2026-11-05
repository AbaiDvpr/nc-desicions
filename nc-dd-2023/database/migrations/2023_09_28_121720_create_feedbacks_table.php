<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Feedback;

/**
 * Миграция для формы "Обратная связь"
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();

            $table->integer('status')->default(Feedback::STATUS_NEW)->comment('Статус заявки');
            $table->string('lang')->nullable()->comment('Язык подачи заявки');
            $table->string('name')->nullable()->comment('Имя');
            $table->string('phone')->nullable()->comment('Телефон');
            $table->string('ip')->nullable()->comment('IP адрес с которого поступила заявка');
            $table->softDeletes()->comment('Дата удаления');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};
