<?php declare(strict_types=1);

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Сущность для хранения Баланса
 */
class Balance extends Model
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
    protected $table = 'companies-balance';

    /**
     * Денежные средства и их эквиваленты
     */
    const NC_10 = 'nc_010';

    /**
     * Краткосрочные финансовые активы, оцениваемые по амортизированной стоимости
     */
    const NC_11 = 'nc_011';

    /**
     * Краткосрочные финансовые активы, оцениваемые по справедливой стоимости через прочий совокупный доход
     */
    const NC_12 = 'nc_012';

    /**
     * Краткосрочные финансовые активы, учитываемые по справедливой стоимости через прибыли или убытки
     */
    const NC_13 = 'nc_013';

    /**
     * Краткосрочные производные финансовые инструменты
     */
    const NC_14 = 'nc_014';

    /**
     * Прочие краткосрочные финансовые активы
     */
    const NC_15 = 'nc_015';

    /**
     * Краткосрочная торговая и прочая дебиторская задолженность
     */
    const NC_16 = 'nc_016';

    /**
     * Краткосрочная дебиторская задолженность по аренде
     */
    const NC_17 = 'nc_017';

    /**
     * Краткосрочные активы по договорам с покупателями
     */
    const NC_18 = 'nc_018';

    /**
     * Текущий подоходный налог
     */
    const NC_19 = 'nc_019';

    /**
     * Запасы
     */
    const NC_20 = 'nc_020';

    /**
     * Биологические активы
     */
    const NC_21 = 'nc_021';

    /**
     * Прочие краткосрочные активы
     */
    const NC_22 = 'nc_022';

    /**
     * Итого краткосрочных активов (сумма строк с 010 по 022)
     */
    const NC_100 = 'nc_100';

    /**
     * Активы (или выбывающие группы), предназначенные для продажи
     */
    const NC_101 = 'nc_101';

    /**
     * Долгосрочные финансовые активы, оцениваемые по амортизированной стоимости
     */
    const NC_110 = 'nc_110';

    /**
     * Долгосрочные финансовые активы, оцениваемые по справедливой стоимости через прочий совокупный доход
     */
    const NC_111 = 'nc_111';

    /**
     * Долгосрочные финансовые активы, учитываемые по справедливой стоимости через прибыли или убытки
     */
    const NC_112 = 'nc_112';

    /**
     * Долгосрочные производные финансовые инструменты
     */
    const NC_113 = 'nc_113';

    /**
     * Инвестиции, учитываемые по первоначальной стоимости
     */
    const NC_114 = 'nc_114';

    /**
     * Инвестиции, учитываемые методом долевого участия
     */
    const NC_115 = 'nc_115';

    /**
     * Прочие долгосрочные финансовые активы
     */
    const NC_116 = 'nc_116';

    /**
     * Долгосрочная торговая и прочая дебиторская задолженность
     */
    const NC_117 = 'nc_117';

    /**
     * Долгосрочная дебиторская задолженность по аренде
     */
    const NC_118 = 'nc_118';

    /**
     * Долгосрочные активы по договорам с покупателями
     */
    const NC_119 = 'nc_119';

    /**
     * Инвестиционное имущество
     */
    const NC_120 = 'nc_120';

    /**
     * Основные средства
     */
    const NC_121 = 'nc_121';

    /**
     * Актив в форме права пользования
     */
    const NC_122 = 'nc_122';

    /**
     * Биологические активы
     */
    const NC_123 = 'nc_123';

    /**
     * Разведочные и оценочные активы
     */
    const NC_124 = 'nc_124';

    /**
     * Нематериальные активы
     */
    const NC_125 = 'nc_125';

    /**
     * Отложенные налоговые активы
     */
    const NC_126 = 'nc_126';

    /**
     * Прочие долгосрочные активы
     */
    const NC_127 = 'nc_127';

    /**
     * Итого долгосрочных активов (сумма строк с 110 по 127)
     */
    const NC_200 = 'nc_200';

    /**
     * Краткосрочные финансовые обязательства, оцениваемые по амортизированной стоимости
     */
    const NC_210 = 'nc_210';

    /**
     * Краткосрочные финансовые обязательства, оцениваемые по справедливой стоимости через прибыль или убыток
     */
    const NC_211 = 'nc_211';

    /**
     * Краткосрочные производные финансовые инструменты
     */
    const NC_212 = 'nc_212';

    /**
     * Прочие краткосрочные финансовые обязательства
     */
    const NC_213 = 'nc_213';

    /**
     * Краткосрочная торговая и прочая кредиторская задолженность
     */
    const NC_214 = 'nc_214';

    /**
     * Краткосрочные оценочные обязательства
     */
    const NC_215 = 'nc_215';

    /**
     * Текущие налоговые обязательства по подоходному налогу
     */
    const NC_216 = 'nc_216';

    /**
     * Вознаграждения работникам
     */
    const NC_217 = 'nc_217';

    /**
     * Краткосрочная задолженность по аренде
     */
    const NC_218 = 'nc_218';

    /**
     * Краткосрочные обязательства по договорам покупателями
     */
    const NC_219 = 'nc_219';

    /**
     * Государственные субсидии
     */
    const NC_220 = 'nc_220';

    /**
     * Дивиденды к оплате
     */
    const NC_221 = 'nc_221';

    /**
     * Прочие краткосрочные обязательства
     */
    const NC_222 = 'nc_222';

    /**
     * Итого краткосрочных обязательств (сумма строк с 210 по 222)
     */
    const NC_300 = 'nc_300';

    /**
     * Обязательства выбывающих групп, предназначенных для продажи
     */
    const NC_301 = 'nc_301';

    /**
     * Долгосрочные финансовые обязательства, оцениваемые по амортизированной стоимости
     */
    const NC_310 = 'nc_310';

    /**
     * Долгосрочные финансовые обязательства, оцениваемые по справедливой стоимости через прибыль или убыток
     */
    const NC_311 = 'nc_311';

    /**
     * Долгосрочные производные финансовые инструменты
     */
    const NC_312 = 'nc_312';

    /**
     * Прочие долгосрочные финансовые обязательства
     */
    const NC_313 = 'nc_313';

    /**
     * Долгосрочная торговая и прочая кредиторская задолженность
     */
    const NC_314 = 'nc_314';

    /**
     * Долгосрочные оценочные обязательства
     */
    const NC_315 = 'nc_315';

    /**
     * Отложенные налоговые обязательства
     */
    const NC_316 = 'nc_316';

    /**
     * Вознаграждения работникам
     */
    const NC_317 = 'nc_317';

    /**
     * Долгосрочная задолженность по аренде
     */
    const NC_318 = 'nc_318';

    /**
     * Долгосрочные обязательства по договорам с покупателями
     */
    const NC_319 = 'nc_319';

    /**
     * Государственные субсидии
     */
    const NC_320 = 'nc_320';

    /**
     * Прочие долгосрочные обязательства
     */
    const NC_321 = 'nc_321';

    /**
     * Итого долгосрочных обязательств (сумма строк с 310 по 321)
     */
    const NC_400 = 'nc_400';

    /**
     * Уставный (акционерный) капитал
     */
    const NC_410 = 'nc_410';

    /**
     * Эмиссионный доход
     */
    const NC_411 = 'nc_411';

    /**
     * Выкупленные собственные долевые инструменты
     */
    const NC_412 = 'nc_412';

    /**
     * Компоненты прочего совокупного дохода
     */
    const NC_413 = 'nc_413';

    /**
     * Нераспределенная прибыль (непокрытый убыток)
     */
    const NC_414 = 'nc_414';

    /**
     * Прочий капитал
     */
    const NC_415 = 'nc_415';

    /**
     * Итого капитал, относимый на собственников материнской организации (сумма строк с 410 по 415)
     */
    const NC_420 = 'nc_420';

    /**
     * Доля неконтролирующих собственников
     */
    const NC_421 = 'nc_421';

    /**
     * Всего капитал (строка 420 +/- строка 421)
     */
    const NC_500 = 'nc_500';

    /**
     * Список кодовых полей
     */
    const CODE_FIELDS = [
        self::NC_10,
        self::NC_11,
        self::NC_12,
        self::NC_13,
        self::NC_14,
        self::NC_15,
        self::NC_16,
        self::NC_17,
        self::NC_18,
        self::NC_19,
        self::NC_20,
        self::NC_21,
        self::NC_22,
        self::NC_100,
        self::NC_101,
        self::NC_110,
        self::NC_111,
        self::NC_112,
        self::NC_113,
        self::NC_114,
        self::NC_115,
        self::NC_116,
        self::NC_117,
        self::NC_118,
        self::NC_119,
        self::NC_120,
        self::NC_121,
        self::NC_122,
        self::NC_123,
        self::NC_124,
        self::NC_125,
        self::NC_126,
        self::NC_127,
        self::NC_200,
        self::NC_210,
        self::NC_211,
        self::NC_212,
        self::NC_213,
        self::NC_214,
        self::NC_215,
        self::NC_216,
        self::NC_217,
        self::NC_218,
        self::NC_219,
        self::NC_220,
        self::NC_221,
        self::NC_222,
        self::NC_300,
        self::NC_301,
        self::NC_310,
        self::NC_311,
        self::NC_312,
        self::NC_313,
        self::NC_314,
        self::NC_315,
        self::NC_316,
        self::NC_317,
        self::NC_318,
        self::NC_319,
        self::NC_320,
        self::NC_321,
        self::NC_400,
        self::NC_410,
        self::NC_411,
        self::NC_412,
        self::NC_413,
        self::NC_414,
        self::NC_415,
        self::NC_420,
        self::NC_421,
        self::NC_500
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
     * @param  string                      $code
     * @param  float                       $value
     * @return \App\Models\Company\Balance
     */
    public function setByCode(string $code, float $value): self
    {
        $this->setAttribute($code, $value);

        return $this;
    }

    /**
     * Возвращает UUID компании
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
     * @param  string                      $companyUuid
     * @return \App\Models\Company\Balance
     */
    public function setCompanyUuid(string $companyUuid): self
    {
        $this->setAttribute('companyUuid', $companyUuid);

        return $this;
    }

    /**
     * Устанавливает период
     *
     * @param  string                      $period
     * @return \App\Models\Company\Balance
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
     * Устанавливает значение, что данные пришли из ручного ведения отчетности
     *
     * @return \App\Models\Company\Balance
     */
    public function setFillTypeHandheld(): self
    {
        $this->setAttribute('fillType', config('company.fillType.input'));

        return $this;
    }

    /**
     * Возвращает общую сумму активов
     *
     * @return float
     */
    public function getAssets(): float
    {
        return $this->getByCode(self::NC_100) + $this->getByCode(self::NC_200);
    }

    /**
     * Возвращает значение для поля "Краткосрочные Активы" (100)
     *
     * @return float
     */
    public function getShortTermAssets(): float
    {
        return $this->getByCode(self::NC_20) +
               $this->getByCode(self::NC_16) +
               $this->getByCode(self::NC_10) +
               $this->getByCode(self::NC_22);
    }

    /**
     * Возвращает значение для поля "Краткосрочные обязательства" (300)
     *
     * @return float
     */
    public function getShortTermLiabilities(): float
    {
        return $this->getByCode(self::NC_222) + $this->getByCode(self::NC_214);
    }

    /**
     * Возвращает значение для поля "Капитал и резервы" (500)
     *
     * @return float
     */
    public function getCapitalAndReserves(): float
    {
        return $this->getByCode(self::NC_222) + $this->getByCode(self::NC_214);
    }
}
