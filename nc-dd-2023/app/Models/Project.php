<?php declare(strict_types=1);

namespace App\Models;

use App\DI;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\AttractingInvestments;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\ProjectForm;
use App\Models\Presenters\Project\Data as ProjectData;
use App\Models\Project\AdministrativeExpenses;
use App\Models\Project\AttractingInvestment;
use App\Models\Project\EquipmentList;
use App\Models\Project\InvestmentAmount;
use App\Models\Project\MarketingExpenses;
use App\Models\Project\OwnInvestment;
use App\Models\Project\ReadyPremisesList;
use App\Models\Project\SalaryFund;
use App\Models\Project\VariableCosts;
use App\Models\Project\VariableCostsOther;
use App\Models\Project\VolumeSales;
use App\Models\Project\WorkingCapital;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Сущность для хранения проектов
 *
 * @method static create(array $array)
 * @method static find(mixed $uuid)
 * @method static firstOrCreate(array $findAttributes, array $otherAttributes)
 * @method static where(string $string, mixed $id)
 * @method static findOrFail(string $param)
 * @property mixed uuid
 * @property mixed status
 * @property int|mixed step
 * @property mixed salesStartDate
 * @property mixed administrativeExpenses
 * @property mixed marketingExpenses
 * @property mixed volumeSales
 * @property mixed equipmentList
 * @property mixed readyPremisesList
 * @property mixed smrList
 * @property mixed ownInvestment
 * @property mixed investmentAmount
 * @property mixed attractingInvestment
 * @property mixed otherIncome
 * @property mixed created_at
 * @property mixed $variableCostsOther
 * @property mixed $workingCapital
 * @property mixed $plannedCapacity
 * @property mixed $turnover
 * @property mixed $schedule             // График работы
 */
class Project extends CoreModel
{
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
    protected $primaryKey = 'uuid';

    /**
     * @var ProjectForm
     */
    private $formProject;

    /**
     * @inheritdoc
     *
     * @var array
     */
    protected $casts = [
        'planning'        => 'array',
        'plannedCapacity' => 'array',
        'turnover'        => 'array',
    ];

    /**
     * @var \App\Models\Presenters\Project\Data
     */
    protected $projectData;

    /**
     * Статус проекта "Новый"
     */
    const STATUS_NEW = 'new';

    /**
     * Статус проекта "Редактируется"
     */
    const STATUS_EDITED = 'edited';

    /**
     * Статус проекта "Завершенные"
     */
    const STATUS_COMPLETED = 'completed';

    /**
     * Список статусов с названиями
     */
    const STATUS_NAMES = [
        self::STATUS_NEW       => 'Новый',
        self::STATUS_EDITED    => 'Редактируется',
        self::STATUS_COMPLETED => 'Завершенные',
    ];

    /**
     * Конструктор
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->formProject = new ProjectForm();
    }

    /**
     * Возвращает связь проекта с пользователем
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Возвращает UUID проекта
     *
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * Возвращает название проекта
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Возвращает дату создания проекта
     *
     * @return \Illuminate\Support\Carbon
     */
    public function getCreatedAt(): Carbon
    {
        return $this->created_at;
    }

    /**
     * Получить значение из элемента form
     *
     * @return mixed
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Получить номер текущего шага
     *
     * @return int
     */
    public function getStep(): int
    {
        return (int) $this->step;
    }

    /**
     * Получить значение из элемента currency
     *
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Получить значение из элемента schedule
     *
     * @return mixed
     */
    public function getSchedule()
    {
        return $this->schedule;
    }

    /**
     * Получить значение из элемента planning
     *
     * @return mixed
     */
    public function getPlanning()
    {
        return $this->planning;
    }

    /**
     * Получить значения "Плановой мощности"
     *
     * @return mixed
     */
    public function getPlannedCapacity()
    {
        return $this->plannedCapacity;
    }

    /**
     * Получить значения оборачиваемости
     *
     * @return mixed
     */
    public function getTurnover()
    {
        return $this->turnover;
    }

    /**
     * Получить значение из элемента time_param
     *
     * @return mixed
     */
    public function getTimeParam()
    {
        return $this->time_param;
    }

    /**
     * Прочие доходы (при наличии)
     *
     * @return mixed
     */
    public function getOtherIncome()
    {
        return $this->otherIncome;
    }

    /**
     * Возвращает ID пользователя
     *
     * @return int
     */
    public function getUserId(): int
    {
        return (int) $this->getAttribute('user_id');
    }

    /**
     * Является ли проект расчетом себестоимости на единицу продукции
     *
     * @return bool
     */
    public function getCostType(): bool
    {
        return (bool) $this->getAttribute('costType');
    }

    /**
     * Устанавливает тип себестоимости на единицу продукции или переменные расходы
     *
     * @param  bool                $cosType
     * @return \App\Models\Project
     */
    public function setCostType(bool $cosType = false): static
    {
        $this->setAttribute('costType', $cosType);

        return $this;
    }

    /**
     * Возвращает объект для работы с данными пользователя
     *
     * @return \App\Models\Presenters\Project\Data
     */
    public function getProjectData(): ProjectData
    {
        if (!$this->projectData) {
            $this->projectData = new ProjectData($this);
        }

        return $this->projectData;
    }

    /**
     * Получаем название статуса на русском языке
     *
     * @return string
     */
    public function getStatusName(): string
    {
        return self::STATUS_NAMES[$this->status];
    }

    /**
     * С НДС ли проект
     *
     * @return boolean
     */
    public function isNds(): bool
    {
        return $this->nds === 1;
    }

    /**
     * Получить элементы распределения привлекаемых инвестиций
     *
     * @return HasOne
     */
    public function investmentAmount(): HasOne
    {
        return $this->hasOne(InvestmentAmount::class, 'uuid', 'uuid');
    }

    /**
     * Будете ли вы участвовать собственными вложениями?
     *
     * @return bool
     */
    public function isOwnInvestment(): bool
    {
        return (bool) $this->own_investments;
    }

    /**
     * Получить элементы распределения собственных вложений
     *
     * @return HasOne
     */
    public function ownInvestment(): HasOne
    {
        return $this->hasOne(OwnInvestment::class, 'uuid', 'uuid');
    }

    /**
     * Получить элементы приобретаемого оборудования
     *
     * @return HasMany
     */
    public function equipmentList(): HasMany
    {
        return $this->hasMany(EquipmentList::class, 'uuid');
    }

    /**
     * Получить элементы условия привлекаемых инвестиций
     *
     * @return HasOne
     */
    public function attractingInvestment(): HasOne
    {
        return $this->hasOne(AttractingInvestment::class, 'uuid', 'uuid');
    }

    /**
     * Получить элементы планируемого объема продаж в месяц
     *
     * @return HasMany
     */
    public function volumeSales(): HasMany
    {
        return $this->hasMany(VolumeSales::class, 'uuid', 'uuid');
    }

    /**
     * Получить элементы фонда заработной платы
     *
     * @return HasMany
     */
    public function salaryFund(): HasMany
    {
        return $this->hasMany(SalaryFund::class, 'uuid', 'uuid');
    }

    /**
     * Получить элементы административных расходов
     *
     * @return HasMany
     */
    public function administrativeExpenses(): HasMany
    {
        return $this->hasMany(AdministrativeExpenses::class, 'uuid', 'uuid');
    }

    /**
     * Получить элементы маркетинговых расходов
     *
     * @return HasMany
     */
    public function marketingExpenses(): HasMany
    {
        return $this->hasMany(MarketingExpenses::class, 'uuid', 'uuid');
    }

    /**
     * Получить элементы "Себестоимость на единицу продукции"
     *
     * @return HasMany
     */
    public function variableCosts(): HasMany
    {
        return $this->hasMany(VariableCosts::class, 'uuid', 'uuid');
    }

    /**
     * Получить элементы "Переменные расходы"
     *
     * @return HasMany
     */
    public function variableCostsOther(): HasMany
    {
        return $this->hasMany(VariableCostsOther::class, 'uuid', 'uuid');
    }

    /**
     * Получить данные по "Пополнение оборотного капитала"
     *
     * @return HasOne
     */
    public function workingCapital(): HasOne
    {
        return $this->hasOne(WorkingCapital::class, 'uuid', 'uuid');
    }

    /**
     * Новый ли проект
     *
     * @return bool
     */
    public function isNew(): bool
    {
        return $this->status === self::STATUS_NEW;
    }

    /**
     * Редактируется ли проект
     *
     * @return bool
     */
    public function isEdited(): bool
    {
        return $this->status === self::STATUS_EDITED;
    }

    /**
     * Завершен ли проект
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Можно ли корректировать проект
     *
     * @return bool
     */
    public function isPossibleToCorrect(): bool
    {
        $client = DI::getRepository()->getUser()->findById($this->getUserId());

        if ($client->hasFullAccess()) {
            return true;
        }

        $history = DI::getRepository()->getProject()->getHistory()->getCurrent($this);

        return $history->getCorrection() < $history->userTariff()->getProjectCorrection();
    }

    /**
     * Сеттер для статуса
     *
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status = ''): self
    {
        if (array_key_exists($status, self::STATUS_NAMES)) {
            $this->status = $status;
        }

        return $this;
    }

    /**
     * Сеттер для шага
     *
     * @param int $step
     * @return $this
     */
    public function setStep(int $step = 1): self
    {
        $this->step = $step;

        return $this;
    }

    /**
     * Вернуть название выбранного unit's
     *
     * @return string
     */
    public function getCurrencyValue(): string
    {
        return (new ProjectForm())->getElementValue('currency', $this->getCurrency());
    }

    /**
     * Получить элементы приобретаемой недвижимости
     *
     * @return HasMany
     */
    public function readyPremisesList(): HasMany
    {
        return $this->hasMany(ReadyPremisesList::class, 'uuid')->where('type', '=', ReadyPremisesList::REAL_ESTATE_TYPE);
    }

    /**
     * Получить элементы списка СМР
     *
     * @return HasMany
     */
    public function smrList(): HasMany
    {
        return $this->hasMany(ReadyPremisesList::class, 'uuid')->where('type', '=', ReadyPremisesList::SMR_TYPE);
    }

    /**
     * Получить элементы списка Капитальный ремонт
     *
     * @return HasMany
     */
    public function overhaulList(): HasMany
    {
        return $this->hasMany(ReadyPremisesList::class, 'uuid')->where('type', '=', ReadyPremisesList::OVERHAUL_TYPE);
    }

    /**
     * На условиях кредитного финансирования?
     *
     * @return bool
     */
    public function isCreditFinancing(): bool
    {
        if ($this->attractingInvestment) {
            return $this->attractingInvestment->investment === AttractingInvestments::CREDIT_FINANCING;
        }

        return false;
    }

    /**
     * На условиях долевого участия?
     *
     * @return bool
     */
    public function isDividends(): bool
    {
        if ($this->attractingInvestment) {
            return $this->attractingInvestment->investment === AttractingInvestments::EQUITY_FINANCING;
        }

        return false;
    }

    /**
     * На условиях грантового финансирования
     *
     * @return bool
     */
    public function isGrantFinancing(): bool
    {
        if ($this->attractingInvestment) {
            return $this->attractingInvestment->investment === AttractingInvestments::GRANT_FINANCING;
        }

        return false;
    }

    /**
     * Проверяет, является ли проект с графиком "На пополнение основных средств"
     *
     * @return bool
     */
    public function isFixedAssets(): bool
    {
        $investmentAmount = $this->investmentAmount;

        if (!is_null($investmentAmount)) {
            return !is_null($investmentAmount->smr) ||
                   !is_null($investmentAmount->overhaul) ||
                   !is_null($investmentAmount->readyPremises) ||
                   !is_null($investmentAmount->equipment);
        }

        return false;
    }

    /**
     * Проверяет, является ли проект с графиком "На пополнение оборотных средств"
     *
     * @return bool
     */
    public function isWorkingCapital(): bool
    {
        $investmentAmount = $this->investmentAmount;

        if (!is_null($investmentAmount)) {
            return !is_null($investmentAmount->raw) ||
                   !is_null($investmentAmount->advertising) ||
                   !is_null($investmentAmount->wages) ||
                   !is_null($investmentAmount->rentalExpenses) ||
                   !is_null($investmentAmount->otherExpenses);
        }

        return false;
    }
}
