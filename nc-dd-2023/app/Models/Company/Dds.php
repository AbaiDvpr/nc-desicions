<?php declare(strict_types=1);

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Сущность для хранения ДДС
 */
class Dds extends Model
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
    protected $table = 'companies-dds';

    /**
     * Поступление денежных средств, всего (сумма строк с 011 по 016)
     */
    const NC_10 = 'nc_010';

    /**
     * Реализация товаров и услуг
     */
    const NC_11 = 'nc_011';

    /**
     * Прочая выручка
     */
    const NC_12 = 'nc_012';

    /**
     * Авансы, полученные от покупателей, заказчиков
     */
    const NC_13 = 'nc_013';

    /**
     * Поступления по договорам страхования
     */
    const NC_14 = 'nc_014';

    /**
     * Полученные вознаграждения
     */
    const NC_15 = 'nc_015';

    /**
     * Прочие поступления
     */
    const NC_16 = 'nc_016';

    /**
     * Выбытие денежных средств, всего (сумма строк с 021 по 027)
     */
    const NC_20 = 'nc_020';

    /**
     * Платежи поставщикам за товары и услуги
     */
    const NC_21 = 'nc_021';

    /**
     * Авансы, выданные поставщикам товаров и услуг
     */
    const NC_22 = 'nc_022';

    /**
     * Выплаты по оплате труда
     */
    const NC_23 = 'nc_023';

    /**
     * Выплата вознаграждения
     */
    const NC_24 = 'nc_024';

    /**
     * Выплаты по договорам страхования
     */
    const NC_25 = 'nc_025';

    /**
     * Подоходный налог и другие платежи в бюджет
     */
    const NC_26 = 'nc_026';

    /**
     * Прочие выплаты
     */
    const NC_27 = 'nc_027';

    /**
     * Чистая сумма денежных средств от операционной деятельности (строка 010 – строка 020)
     */
    const NC_30 = 'nc_030';

    /**
     * Поступление денежных средств, всего (сумма строк с 041 по 052)
     */
    const NC_40 = 'nc_040';

    /**
     * Реализация основных средств
     */
    const NC_41 = 'nc_041';

    /**
     * Реализация нематериальных активов
     */
    const NC_42 = 'nc_042';

    /**
     * Реализация других долгосрочных активов
     */
    const NC_43 = 'nc_043';

    /**
     * Реализация долевых инструментов других организаций (кроме дочерних)
     * и долей участия в совместном предпринимательстве
     */
    const NC_44 = 'nc_044';

    /**
     * Реализация долговых инструментов других организаций
     */
    const NC_45 = 'nc_045';

    /**
     * Возмещение при потере контроля над дочерними организациями
     */
    const NC_46 = 'nc_046';

    /**
     * Изъятие денежных вкладов
     */
    const NC_47 = 'nc_047';

    /**
     * Реализация прочих финансовых активов
     */
    const NC_48 = 'nc_048';

    /**
     * Фьючерсные и форвардные контракты, опционы и свопы
     */
    const NC_49 = 'nc_049';

    /**
     * Полученные дивиденды
     */
    const NC_50 = 'nc_050';

    /**
     * Полученные вознаграждения
     */
    const NC_51 = 'nc_051';

    /**
     * Прочие поступления (погашение выданных займов)
     */
    const NC_52 = 'nc_052';

    /**
     * Выбытие денежных средств, всего (сумма строк с 061 по 071)
     */
    const NC_60 = 'nc_060';

    /**
     * Приобретение основных средств
     */
    const NC_61 = 'nc_061';

    /**
     * Приобретение нематериальных активов
     */
    const NC_62 = 'nc_062';

    /**
     * Приобретение других долгосрочных активов
     */
    const NC_63 = 'nc_063';

    /**
     * Приобретение долевых инструментов других организаций (кроме дочерних)
     * и долей участия в совместном предпринимательстве
     */
    const NC_64 = 'nc_064';

    /**
     * Приобретение долговых инструментов других организаций
     */
    const NC_65 = 'nc_065';

    /**
     * Приобретение контроля над дочерними организациями
     */
    const NC_66 = 'nc_066';

    /**
     * Размещение денежных вкладов
     */
    const NC_67 = 'nc_067';

    /**
     * Выплата вознаграждения
     */
    const NC_68 = 'nc_068';

    /**
     * Приобретение прочих финансовых активов
     */
    const NC_69 = 'nc_069';

    /**
     * Предоставление займов
     */
    const NC_70 = 'nc_070';

    /**
     * Фьючерсные и форвардные контракты, опционы и свопы
     */
    const NC_71 = 'nc_071';

    /**
     * Инвестиции в ассоциированные и дочерние организации
     */
    const NC_72 = 'nc_072';

    /**
     * Прочие выплаты
     */
    const NC_73 = 'nc_073';

    /**
     * Чистая сумма денежных средств от инвестиционной деятельности (строка 040 – строка 060)
     */
    const NC_80 = 'nc_080';

    /**
     * Поступление денежных средств, всего (сумма строк с 091 по 094)
     */
    const NC_90 = 'nc_090';

    /**
     * Эмиссия акций и других финансовых инструментов
     */
    const NC_91 = 'nc_091';

    /**
     * Получение займов
     */
    const NC_92 = 'nc_092';

    /**
     * Полученные вознаграждения
     */
    const NC_93 = 'nc_093';

    /**
     * Прочие поступления
     */
    const NC_94 = 'nc_094';

    /**
     * Выбытие денежных средств, всего (сумма строк с 101 по 105)
     */
    const NC_100 = 'nc_100';

    /**
     * Погашение займов
     */
    const NC_101 = 'nc_101';

    /**
     * Выплата вознаграждения
     */
    const NC_102 = 'nc_102';

    /**
     * Выплата дивидендов
     */
    const NC_103 = 'nc_103';

    /**
     * Выплаты собственникам по акциям организации
     */
    const NC_104 = 'nc_104';

    /**
     * Прочие выбытия
     */
    const NC_105 = 'nc_105';

    /**
     * Чистая сумма денежных средств от финансовой деятельности (строка 090 – строка 100)
     */
    const NC_110 = 'nc_110';

    /**
     * Влияние обменных курсов валют к тенге
     */
    const NC_120 = 'nc_120';

    /**
     * Влияние изменения балансовой стоимости денежных средств и их эквивалентов
     */
    const NC_130 = 'nc_130';

    /**
     * Увеличение +/- уменьшение денежных средств
     * (строка 030 +/- строка 080 +/- строка 110 +/- строка 120+/- строка 130
     */
    const NC_140 = 'nc_140';

    /**
     * Денежные средства и их эквиваленты на начало отчетного периода
     */
    const NC_150 = 'nc_150';

    /**
     * Денежные средства и их эквиваленты на конец отчетного периода
     */
    const NC_160 = 'nc_160';

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
        self::NC_20,
        self::NC_21,
        self::NC_22,
        self::NC_23,
        self::NC_24,
        self::NC_25,
        self::NC_26,
        self::NC_27,
        self::NC_30,
        self::NC_40,
        self::NC_41,
        self::NC_42,
        self::NC_43,
        self::NC_44,
        self::NC_45,
        self::NC_46,
        self::NC_47,
        self::NC_48,
        self::NC_49,
        self::NC_50,
        self::NC_51,
        self::NC_52,
        self::NC_60,
        self::NC_61,
        self::NC_62,
        self::NC_63,
        self::NC_64,
        self::NC_65,
        self::NC_66,
        self::NC_67,
        self::NC_68,
        self::NC_69,
        self::NC_70,
        self::NC_71,
        self::NC_72,
        self::NC_73,
        self::NC_80,
        self::NC_90,
        self::NC_91,
        self::NC_92,
        self::NC_93,
        self::NC_94,
        self::NC_100,
        self::NC_101,
        self::NC_102,
        self::NC_103,
        self::NC_104,
        self::NC_105,
        self::NC_110,
        self::NC_120,
        self::NC_130,
        self::NC_140,
        self::NC_150,
        self::NC_160
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
     * @return \App\Models\Company\Dds
     */
    public function setByCode(string $code, float $value): self
    {
        $this->setAttribute($code, $value);

        return $this;
    }

    /**
     * Устанавливает значение, что данные пришли из ручного ведения отчетности
     *
     * @return \App\Models\Company\Dds
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
     * @return \App\Models\Company\Dds
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
     * @return \App\Models\Company\Dds
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
     * Возвращает "Денежный поток от операционной деятельности"
     *
     * @return float
     */
    public function getCashFlow(): float
    {
        return $this->getByCode(self::NC_10) - $this->getByCode(self::NC_20);
    }

    /**
     * Возвращает "Денежный поток от инвестиционной деятельности"
     *
     * @return float
     */
    public function getCashFlowFromInvestingActivities(): float
    {
        return $this->getByCode(self::NC_40) - $this->getByCode(self::NC_60);
    }

    /**
     * Возвращает "Денежный поток от финансовой деятельности"
     *
     * @return float
     */
    public function getCashFlowFromFinancingActivities(): float
    {
        return $this->getByCode(self::NC_90) - $this->getByCode(self::NC_100);
    }

    /**
     * Возвращает "Общий денежный поток"
     *
     * @return float
     */
    public function getTotalCashFlow(): float
    {
        return $this->getByCode(self::NC_30) + $this->getByCode(self::NC_80) + $this->getByCode(self::NC_110);
    }
}
