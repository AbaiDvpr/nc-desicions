<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColServicesIntoProjectsSalaryFundTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects-salary-fund', function (Blueprint $table) {
            $table->json('services')->nullable()->after('industrial')->comment('Услуги');
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
            $table->dropColumn('services');
        });
    }
}
