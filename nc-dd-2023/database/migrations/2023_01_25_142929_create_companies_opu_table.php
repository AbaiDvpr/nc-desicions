<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция для работы с опу компании
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
        Schema::create('companies-opu', function (Blueprint $table) {
            $table->id();
            $table->uuid('companyUuid');
            $table->foreign('companyUuid')->references('uuid')->on('companies')->cascadeOnDelete();

            $table->date('period')->comment('Период');

            foreach (config('company.opu') as $code => $codeValue) {
                $collName = 'nc_' . $code;

                if (!Schema::hasColumn('companies-opu', $collName)) {
                    $table->decimal($collName, 30, 5)->nullable()->comment($codeValue);
                }
            }

            $table->softDeletes()->comment('Дата удаления');

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('companies-opu');
    }
};
