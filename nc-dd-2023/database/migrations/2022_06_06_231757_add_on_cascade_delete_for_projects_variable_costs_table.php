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
        Schema::table('projects-variable-costs', function (Blueprint $table) {
            $table->dropForeign('projects_variable_costs_vs_id_foreign');
        });

        Schema::table('projects-variable-costs', function (Blueprint $table) {
            $table->foreign('vs_id')->references('id')->on('projects-volume-sales')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
