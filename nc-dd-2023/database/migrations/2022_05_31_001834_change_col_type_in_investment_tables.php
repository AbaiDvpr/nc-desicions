<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        // Увеличиваем столбцы для собственных вложений
        $ownInvestments = 'projects-own-investments';
        Schema::table('`' . $ownInvestments . '`', function (Blueprint $table) use ($ownInvestments) {
            DB::statement($this->getSqlForBigIntCol($ownInvestments, 'smr', 'СМР'));
            DB::statement($this->getSqlForBigIntCol($ownInvestments, 'overhaul', 'Капитальный ремонт'));
            DB::statement($this->getSqlForBigIntCol($ownInvestments, 'readyPremises', 'Готовые помещения'));
            DB::statement($this->getSqlForBigIntCol($ownInvestments, 'equipment', 'Приобретение оборудования'));
            DB::statement($this->getSqlForBigIntCol($ownInvestments, 'raw', 'Закуп сырья'));
            DB::statement($this->getSqlForBigIntCol($ownInvestments, 'advertising', 'Расходы на рекламу'));
            DB::statement($this->getSqlForBigIntCol($ownInvestments, 'wages', 'Расходы по выплате заработной платы'));
            DB::statement($this->getSqlForBigIntCol($ownInvestments, 'rentalExpenses', 'Расходы по аренде'));
            DB::statement($this->getSqlForBigIntCol($ownInvestments, 'otherExpenses', 'Прочие расходы'));
        });

        // Увеличиваем столбцы для привлекаемых инвестиций
        $investment = 'projects-investment-amounts';
        Schema::table('`' . $investment . '`', function (Blueprint $table) use ($investment) {
            DB::statement($this->getSqlForBigIntCol($investment, 'smr', 'СМР'));
            DB::statement($this->getSqlForBigIntCol($investment, 'overhaul', 'Капитальный ремонт'));
            DB::statement($this->getSqlForBigIntCol($investment, 'readyPremises', 'Готовые помещения'));
            DB::statement($this->getSqlForBigIntCol($investment, 'equipment', 'Приобретение оборудования'));
            DB::statement($this->getSqlForBigIntCol($investment, 'raw', 'Закуп сырья'));
            DB::statement($this->getSqlForBigIntCol($investment, 'advertising', 'Расходы на рекламу'));
            DB::statement($this->getSqlForBigIntCol($investment, 'wages', 'Расходы по выплате заработной платы'));
            DB::statement($this->getSqlForBigIntCol($investment, 'rentalExpenses', 'Расходы по аренде'));
            DB::statement($this->getSqlForBigIntCol($investment, 'otherExpenses', 'Прочие расходы'));
        });

        $readyPremises = 'projects-ready-premises-lists';
        Schema::table('`' . $readyPremises . '`', function (Blueprint $table) use ($readyPremises) {
            DB::statement($this->getSqlForBigIntCol($readyPremises, 'priceOneUnit', 'Цена за ед'));
            DB::statement($this->getSqlForBigIntCol($readyPremises, 'price', 'Стоимость'));
        });

        $equipmentLists = 'projects-equipment-lists';
        Schema::table('`' . $equipmentLists . '`', function (Blueprint $table) use ($equipmentLists) {
            DB::statement($this->getSqlForBigIntCol($equipmentLists, 'priceOneUnit', 'Цена за ед'));
            DB::statement($this->getSqlForBigIntCol($equipmentLists, 'price', 'Стоимость'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('`projects-own-investments`', function (Blueprint $table) {
            //
        });

        Schema::table('`projects-investment-amounts`', function (Blueprint $table) {
            //
        });
    }

    /**
     * Формирует запрос на модификацию колонки на тип с BIGINT unsigned
     *
     * @param  string $table
     * @param  string $column
     * @param  string $comment
     * @return string
     */
    private function getSqlForBigIntCol(string $table, string $column, string $comment): string
    {
        return 'ALTER TABLE `'.$table.'` MODIFY '.$column.' bigint unsigned null comment "'.$comment.'";';
    }
};
