<?php declare(strict_types=1);

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Сущность для хранения ОПУ
 */
class Opu extends Model
{
    use SoftDeletes;

    /**
     * @inheritdoc
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * @inheritdoc
     *
     * @var string
     */
    protected $table = 'companies-opu';

    /**
     * Выручка
     */
    const NC_10 = 'nc_010';

    /**
     * Себестоимость реализованных товаров и услуг
     */
    const NC_11 = 'nc_011';

    /**
     * Валовая прибыль (строка 010 – строка 011)
     */
    const NC_12 = 'nc_012';

    /**
     * Расходы по реализации
     */
    const NC_13 = 'nc_013';

    /**
     * Административные расходы
     */
    const NC_14 = 'nc_014';

    /**
     * Итого операционная прибыль (убыток) (+/- строки с 012 по 016)
     */
    const NC_20 = 'nc_020';

    /**
     * Финансовые доходы
     */
    const NC_21 = 'nc_021';

    /**
     * Финансовые расходы
     */
    const NC_22 = 'nc_022';

    /**
     * Доля организации в прибыли (убытке) ассоциированных организаций и совместной деятельности,
     * учитываемых по методу долевого участия
     */
    const NC_23 = 'nc_023';

    /**
     * Прочие доходы
     */
    const NC_24 = 'nc_024';

    /**
     * Прочие расходы
     */
    const NC_25 = 'nc_025';

    /**
     * Прибыль (убыток) до налогообложения (+/- строки с 020 по 025)
     */
    const NC_100 = 'nc_100';

    /**
     * Расходы (-) (доходы (+)) по подоходному налогу
     */
    const NC_101 = 'nc_101';

    /**
     * Прибыль (убыток) после налогообложения от продолжающейся деятельности (строка 100 + строка 101)
     */
    const NC_200 = 'nc_200';

    /**
     * Прибыль (убыток) после налогообложения от прекращенной деятельности
     */
    const NC_201 = 'nc_201';

    /**
     * Прибыль за год (строка 200 + строка 201) относимая на:
     * собственников материнской организации, долю неконтролирующих собственников
     */
    const NC_300 = 'nc_300';

    /**
     * Прочий совокупный доход, всего (сумма 420 и 440)
     */
    const NC_400 = 'nc_400';

    /**
     * Переоценка долговых финансовых инструментов, оцениваемых
     * по справедливой стоимости через прочий совокупный доход
     */
    const NC_410 = 'nc_410';

    /**
     * Доля в прочем совокупном доходе (убытке) ассоциированных организаций и совместной деятельности,
     * учитываемых по методу долевого участия
     */
    const NC_411 = 'nc_411';

    /**
     * Эффект изменения в ставке подоходного налога на отсроченный налог
     */
    const NC_412 = 'nc_412';

    /**
     * Хеджирование денежных потоков
     */
    const NC_413 = 'nc_413';

    /**
     * Курсовая разница по инвестициям в зарубежные организации
     */
    const NC_414 = 'nc_414';

    /**
     * Хеджирование чистых инвестиций в зарубежные операции
     */
    const NC_415 = 'nc_415';

    /**
     * Прочие компоненты прочего совокупного дохода
     */
    const NC_416 = 'nc_416';

    /**
     * Корректировка при ре-классификации в составе прибыли (убытка)
     */
    const NC_417 = 'nc_417';

    /**
     * Налоговый эффект компонентов прочего совокупного дохода
     */
    const NC_418 = 'nc_418';

    /**
     * Итого прочий совокупный доход, подлежащий ре-классификации в доходы или расходы в последующие периоды
     * (за вычетом налога на прибыль) (сумма строк с 410 по 418)
     */
    const NC_420 = 'nc_420';

    /**
     * Переоценка основных средств и нематериальных активов
     */
    const NC_431 = 'nc_431';

    /**
     * Доля в прочем совокупном доходе (убытке) ассоциированных организаций и совместной деятельности,
     * учитываемых по методу долевого участия
     */
    const NC_432 = 'nc_432';

    /**
     * Актуальные прибыли (убытки) по пенсионным обязательствам
     */
    const NC_433 = 'nc_433';

    /**
     * Налоговый эффект компонентов прочей совокупной прибыли
     */
    const NC_434 = 'nc_434';

    /**
     * Переоценка долевых финансовых инструментов, оцениваемых
     * по справедливой стоимости через прочий совокупный доход
     */
    const NC_435 = 'nc_435';

    /**
     * Итого прочий совокупный доход, не подлежащий ре-классификации в доходы или расходы в последующие
     * периоды (за вычетом налога на прибыль) (сумма строк с 431 по 435)
     */
    const NC_440 = 'nc_440';

    /**
     * Общий совокупный доход (строка 300 + строка 400)
     */
    const NC_500 = 'nc_500';

    /**
     * Прибыль на акцию
     */
    const NC_600 = 'nc_600';

    /**
     * Список кодовых полей
     */
    const CODE_FIELDS = [
        self::NC_10,
        self::NC_11,
        self::NC_12,
        self::NC_13,
        self::NC_14,
        self::NC_20,
        self::NC_21,
        self::NC_22,
        self::NC_23,
        self::NC_24,
        self::NC_25,
        self::NC_100,
        self::NC_101,
        self::NC_200,
        self::NC_201,
        self::NC_300,
        self::NC_400,
        self::NC_410,
        self::NC_411,
        self::NC_412,
        self::NC_413,
        self::NC_414,
        self::NC_415,
        self::NC_416,
        self::NC_417,
        self::NC_418,
        self::NC_420,
        self::NC_431,
        self::NC_432,
        self::NC_433,
        self::NC_434,
        self::NC_435,
        self::NC_440,
        self::NC_500,
        self::NC_600,
    ];

    /**
     * Возвращает значение полей по коду
     *
     * @param  string $code
     * @return float
     */
    public function getByCode(string $code): float
    {
        return (float) $this->getAttribute($code);
    }

    /**
     * Устанавливает значение поля по коду
     *
     * @param  string                  $code
     * @param  float                   $value
     * @return \App\Models\Company\Opu
     */
    public function setByCode(string $code, float $value): self
    {
        $this->setAttribute($code, $value);

        return $this;
    }

    /**
     * Устанавливает значение, что данные пришли из ручного ведения отчетности
     *
     * @return \App\Models\Company\Opu
     */
    public function setFillTypeHandheld(): self
    {
        $this->setAttribute('fillType', config('company.fillType.input'));

        return $this;
    }

    /**
     * Возвращает значение полей по коду
     *
     * @return string
     */
    public function getCompanyUuid(): string
    {
        return (string) $this->getAttribute('companyUuid');
    }

    /**
     * Устанавливает UUID компании
     *
     * @param  string                  $companyUuid
     * @return \App\Models\Company\Opu
     */
    public function setCompanyUuid(string $companyUuid): self
    {
        $this->setAttribute('companyUuid', $companyUuid);

        return $this;
    }

    /**
     * Устанавливает период
     *
     * @param  string                  $period
     * @return \App\Models\Company\Opu
     */
    public function setPeriod(string $period): static
    {
        $this->setAttribute('period', $period);

        return $this;
    }

    /**
     * Возвращает период
     *
     * @return string
     */
    public function getPeriod(): string
    {
        return $this->getAttribute('period');
    }

    /**
     * Возвращает валовую (маржинальную) прибыль
     *
     * @return float
     */
    public function getGrossProfit(): float
    {
        return $this->getByCode(self::NC_10) - $this->getByCode(self::NC_11);
    }

    /**
     * Возвращает сумму "Косвенные расходы"
     *
     * @return float
     */
    public function getIndirectCosts(): float
    {
        return $this->getByCode(self::NC_11) + $this->getByCode(self::NC_13) + $this->getByCode(self::NC_14);
    }

    /**
     * Возвращает операционную прибыль (EBITDA)
     *
     * @return float
     */
    public function getOperatingProfit(): float
    {
        return $this->getGrossProfit() - $this->getIndirectCosts();
    }
}
