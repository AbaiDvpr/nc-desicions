<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColPercentIntoSalaryFundTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects-salary-fund', function (Blueprint $table) {
            $table->integer('percent')->nullable()->after('salaryPerMonth')->comment('процент от продаж');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('projects-salary-fund', function (Blueprint $table) {
            $table->dropColumn('percent');
        });
    }
}
