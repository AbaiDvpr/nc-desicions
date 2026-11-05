<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeColIntoReadyPremisesListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects-ready-premises-lists', function (Blueprint $table) {
            $table->integer('type')->default(1)->after('lifeTime')
                ->comment('Амортизация на: 1 - Здания, 2 - СМР, 3 - Ремонт');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('projects-ready-premises-lists', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
}
