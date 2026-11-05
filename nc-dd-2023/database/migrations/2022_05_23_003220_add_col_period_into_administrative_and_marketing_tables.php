<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects-administrative-expenses', function (Blueprint $table) {
            $table->integer('period')->default(1)->after('invest')->comment('Период затрат');
        });

        Schema::table('projects-marketing-expenses', function (Blueprint $table) {
            $table->integer('period')->default(1)->after('price')->comment('Период затрат');
            $table->boolean('invest')->default(false)->after('price')->comment('Расходы на инвест период');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('projects-administrative-expenses', function (Blueprint $table) {
            $table->dropColumn('period');
        });

        Schema::table('projects-marketing-expenses', function (Blueprint $table) {
            $table->dropColumn('period');
            $table->dropColumn('invest');
        });
    }
};
