<?php declare(strict_types=1);

namespace App\Models\Company\Filters;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Класс для работы с параметрами запроса для получения факта и плана
 */
class ReportParams
{
    /**
     * Период
     *
     * @var array
     */
    protected array $period;

    /**
     * Uuid компании
     *
     * @var string
     */
    protected string $companyUuid;

    /**
     * ID показателя (для плана)
     *
     * @var int
     */
    protected int $indicatorId;

    /**
     * Создает экземпляр класса заполняя свойства данными из запроса
     *
     * @param  \Illuminate\Http\Request                $request
     * @return \App\Models\Company\Filters\ReportParams
     */
    public static function createFromRequest(Request $request): ReportParams
    {
        $params = new self();

        $params->companyUuid = (string) $request->input('companyUuid');
        $params->period      = (array) $request->input('period');
        $params->indicatorId = (int) $request->input('indicatorId');

        return $params;
    }

    /**
     * Возвращает uuid компании
     *
     * @return string
     */
    public function getCompanyUuid(): string
    {
        return $this->companyUuid;
    }

    /**
     * Возвращает id показателя
     *
     * @return int
     */
    public function getIndicatorId(): int
    {
        return $this->indicatorId;
    }

    /**
     * Возвращает период
     *
     * @return array
     */
    public function getPeriod(): array
    {
        return $this->period;
    }

    /**
     * Возвращает период "from"
     *
     * @return \Illuminate\Support\Carbon
     */
    public function getPeriodFrom(): Carbon
    {
        return Carbon::parse($this->period[0]);
    }

    /**
     * Возвращает период "to"
     *
     * @return \Illuminate\Support\Carbon
     */
    public function getPeriodTo(): Carbon
    {
        return Carbon::parse($this->period[1]);
    }

    /**
     * Возвращает список заполненных свойств в виде массива
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_filter([
            'companyUuid' => $this->companyUuid,
            'period'      => $this->period,
            'indicatorId' => $this->indicatorId,
        ]);
    }
}
