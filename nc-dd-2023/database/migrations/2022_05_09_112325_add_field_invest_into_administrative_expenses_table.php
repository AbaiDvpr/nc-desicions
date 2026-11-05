<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldInvestIntoAdministrativeExpensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects-administrative-expenses', function (Blueprint $table) {
            $table->boolean('invest')->default(false)
                ->after('price')->comment('Расходы на инвест период');
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
            $table->dropColumn('invest');
        });
    }
}
