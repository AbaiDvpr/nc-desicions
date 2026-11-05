<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColIndustrialIntoProjectsSalaryFundTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects-salary-fund', function (Blueprint $table) {
            $table->boolean('industrial')->default(false)
                ->after('salaryPerMonth')->comment('Производственный сотрудник');
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
            $table->dropColumn('industrial');
        });
    }
}
