<?php declare(strict_types=1);
namespace App\Components;

use App\Components\Accounting\Balance;
use App\Components\Accounting\Dds;
use App\Components\Accounting\Opu;
use App\Models\Accounting as AccountingModel;
use App\Models\Company;
use App\Models\Company\Balance as BalanceFact;
use App\Models\Company\Opu as OpuFact;
use App\Models\Company\Dds as DdsFact;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Компонент для работы с ежедневным учетом
 */
class Accounting implements ComponentInterface
{

    /**
     * Обновление всех отчетов на основе ручного ввода
     *
     * @param  \App\Models\Accounting $accounting
     * @return void
     * @throws \Exception
     */
    public function refreshFact(AccountingModel $accounting): void
    {
        $opu = $this->updateOpu($accounting);

        $dds = $this->updateDds($accounting);

        try {
            $this->updateBalance($accounting, $opu, $dds);
        } catch (\Throwable $throwable) {
            Log::error('Не удалось обновить баланс', [
                'exception' => $throwable,
            ]);
        }
    }

    /**
     * Обновляет фактические показатели ОПУ
     *
     * @param  \App\Models\Accounting  $accounting
     * @return \App\Models\Company\Opu
     * @throws \Exception
     */
    public function updateOpu(AccountingModel $accounting): OpuFact
    {
        $opu = (new Opu())->update($accounting);

        // обновляем отчет за определенный период
        $result = Artisan::call('company:report', [
            '--uuid'   => $opu->getCompanyUuid(),
            '--type'   => Company::TYPE_OPU,
            '--period' => $opu->getPeriod(),
        ]);

        if ($result >= 1) {
            throw new \Exception('[ОПУ] команда завершилась ошибкой');
        }

        return $opu;
    }

    /**
     * Обновляет фактические показатели ДДС
     *
     * @param  \App\Models\Accounting  $accounting
     * @return \App\Models\Company\Dds
     * @throws \Exception
     */
    public function updateDds(AccountingModel $accounting): DdsFact
    {
        $dds = (new Dds())->update($accounting);

        // обновляем отчет за определенный период
        $result = Artisan::call('company:report', [
            '--uuid'   => $dds->getCompanyUuid(),
            '--type'   => Company::TYPE_DDS,
            '--period' => $dds->getPeriod(),
        ]);

        if ($result >= 1) {
            throw new \Exception('[ОПУ] команда завершилась ошибкой');
        }

        return $dds;
    }

    /**
     * Обновляет фактические показатели Баланса
     *
     * @param  \App\Models\Accounting      $accounting
     * @param  \App\Models\Company\Opu     $opu
     * @param  \App\Models\Company\Dds     $dds
     * @return \App\Models\Company\Balance
     * @throws \Exception
     */
    public function updateBalance(AccountingModel $accounting, OpuFact $opu, DdsFact $dds): BalanceFact
    {
        $balance = (new Balance())->update($accounting, $opu, $dds);

        $result = Artisan::call('company:report', [
            '--uuid'   => $balance->getCompanyUuid(),
            '--type'   => Company::TYPE_BALANCE,
            '--period' => $balance->getPeriod(),
        ]);

        if ($result >= 1) {
            throw new \Exception('[Баланс] команда завершилась ошибкой');
        }

        return $balance;
    }
}