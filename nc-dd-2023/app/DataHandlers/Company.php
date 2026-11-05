<?php declare(strict_types=1);

namespace App\DataHandlers;

use Illuminate\Support\Carbon;
use App\DataHandlers\Company\Reports\{ Opu, Dds, Balance };

/**
 * Базовый DH для компании
 */
class Company implements DataHandlerInterface
{
    /**
     * Возвращает DH для работы c ОПУ
     *
     * @return \App\DataHandlers\Company\Reports\Opu
     */
    public function getOpu(): Opu
    {
        return new Opu();
    }

    /**
     * Возвращает DH для работы c ДДС
     *
     * @return \App\DataHandlers\Company\Reports\Dds
     */
    public function getDds(): Dds
    {
        return new Dds();
    }

    /**
     * Возвращает DH для работы c Балансом
     *
     * @return \App\DataHandlers\Company\Reports\Balance
     */
    public function getBalance(): Balance
    {
        return new Balance();
    }
}
