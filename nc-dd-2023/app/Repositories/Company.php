<?php declare(strict_types=1);

namespace App\Repositories;

use App\Models\Company as CompanyModel;
use App\Models\Company\Forms\Company as CompanyForm;
use App\Models\User as UserModel;
use App\Models\Company\Filters\PagerParams;
use App\Repositories\Company\Balance;
use App\Repositories\Company\Dds;
use App\Repositories\Company\Import;
use App\Repositories\Company\Indicators;
use App\Repositories\Company\Opu;
use App\Repositories\Company\Reports\Fact\Balance as BalanceReport;
use App\Repositories\Company\Reports\Fact\Dds as DdsReport;
use App\Repositories\Company\Reports\Fact\Opu as OpuReport;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Репозиторий для работы с Компаниями
 */
class Company extends BaseRepository
{
    /**
     * Ключ кеша для компаний
     */
    const CACHE_KEY = 'COMPANY';

    /**
     * Время жизни кеша (1 день)
     */
    const CACHE_LIVE_TIME = 86400;

    /**
     * @var $importFile
     */
    protected $importFile;

    /**
     * @var $opu
     */
    protected $opu;

    /**
     * @var $opuReport
     */
    protected $opuReport;

    /**
     * @var $dds
     */
    protected $dds;

    /**
     * @var $ddsReport
     */
    protected $ddsReport;

    /**
     * @var $balance
     */
    protected $balance;

    /**
     * @var $balanceReport
     */
    protected $balanceReport;

    /**
     * @var $indicators
     */
    protected $indicators;

    /**
     * Сущность для работы импорт файлами компании
     *
     * @return \App\Repositories\Company\Import
     */
    public function getImport(): Import
    {
        if (!$this->importFile) {
            $this->importFile = new Import();
        }

        return $this->importFile;
    }

    /**
     * Сущность для работы с ОПУ
     *
     * @return \App\Repositories\Company\Opu
     */
    public function getOpu(): Opu
    {
        if (!$this->opu) {
            $this->opu = new Opu();
        }

        return $this->opu;
    }

    /**
     * Сущность для работы с отчетами ОПУ
     *
     * @return \App\Repositories\Company\Reports\Fact\Opu
     */
    public function getOpuReport(): OpuReport
    {
        if (!$this->opuReport) {
            $this->opuReport = new OpuReport();
        }

        return $this->opuReport;
    }

    /**
     * Сущность для работы с ДДС
     *
     * @return \App\Repositories\Company\Dds
     */
    public function getDds(): Dds
    {
        if (!$this->dds) {
            $this->dds = new Dds();
        }

        return $this->dds;
    }

    /**
     * Сущность для работы с отчетами ДДС
     *
     * @return \App\Repositories\Company\Reports\Fact\Dds
     */
    public function getDdsReport(): DdsReport
    {
        if (!$this->ddsReport) {
            $this->ddsReport = new DdsReport();
        }

        return $this->ddsReport;
    }

    /**
     * Сущность для работы с Балансом
     *
     * @return \App\Repositories\Company\Balance
     */
    public function getBalance(): Balance
    {
        if (!$this->balance) {
            $this->balance = new Balance();
        }

        return $this->balance;
    }

    /**
     * Сущность для работы с отчетами Баланса
     *
     * @return \App\Repositories\Company\Reports\Fact\Balance
     */
    public function getBalanceReport(): BalanceReport
    {
        if (!$this->balanceReport) {
            $this->balanceReport = new BalanceReport();
        }

        return $this->balanceReport;
    }

    /**
     * Сущность для работы показателями
     *
     * @return \App\Repositories\Company\Indicators
     */
    public function getIndicators(): Indicators
    {
        if (!$this->indicators) {
            $this->indicators = new Indicators();
        }

        return $this->indicators;
    }

    /**
     * Возвращает запись по uuid
     *
     * @param  string                  $uuid
     * @param  bool                    $useCache
     * @return \App\Models\Company|null
     */
    public function getByUuid(string $uuid, bool $useCache = true): ?CompanyModel
    {
        $func = function() use ($uuid) {
            return CompanyModel::find($uuid);
        };

        if ($useCache) {
            return $this->getCache()->getCached(
                $this->getCacheKey(self::CACHE_KEY, $uuid),
                $func,
                self::CACHE_LIVE_TIME
            );
        }

        return $func();
    }

    /**
     * Возвращает список записей
     *
     * @param  array $params
     * @return array
     */
    public function getFromParams(array $params): array
    {
        $companies = new CompanyModel();

        if (isset($params['userId'])) {
            $companies = $companies->where('userId', (int)$params['userId']);
        }

        if (isset($params['tariffId'])) {
            $companies = $companies->where('tariffId', (int)$params['tariffId']);
        }

        if (isset($params['name'])) {
            $companies = $companies->where('name', (string)$params['name']);
        }

        $records = $companies->offset((int)$params['offset'])->limit((int)$params['limit'])->latest()->get();

        return collect($records)->map(function ($record) {
            return $record->getCompanyData()->getPublicData();
        })->toArray();
    }

    /**
     * Возвращает список компаний с использованием фильра
     *
     * @param \App\Models\Company\Filters\PagerParams     $params
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getWithPaginate(PagerParams $params): LengthAwarePaginator
    {
        $companies = new CompanyModel();

        if ($params->getUserId()) {
            $companies = $companies->where('userId', $params->getUserId());
        }

        if ($params->getName()) {
            $companies = $companies->where('name', 'LIKE', '%' . $params->getName() . '%');
        }

        if ($params->getCurrency()) {
            $companies = $companies->where('currency', $params->getCurrency());
        }

        if ($params->getTariffId()) {
            $companies = $companies->where('tariffId', $params->getTariffId());
        }

        if ($params->getFromDate()) {
            $fromDate  = Carbon::parse($params->getFromDate());
            $companies = $companies->where('created_at', '>=', $fromDate);
        }

        if ($params->getToDate()) {
            $fromDate  = Carbon::parse($params->getToDate());
            $companies = $companies->where('created_at', '<=', $fromDate);
        }

        return $companies->latest()->paginate($params->getLimit());
    }

    /**
     * Создает новую или обновляет существующую запись
     *
     * @param  \App\Models\User                  $user
     * @param  \App\Models\Company\Forms\Company $form
     * @return \App\Models\Company|null
     */
    public function save(UserModel $user, CompanyForm $form): ?CompanyModel
    {
        return DB::transaction(function () use ($user, $form) {
            $company = $form->getUuid() ? $this->getByUuid($form->getUuid()) : new CompanyModel();

            if (!$form->getUuid()) {
                $company->setUserId($user->getAuthIdentifier());
                $company->setTariffId($user->tariff()->getId());
            }

            $company->setName($form->getName());
            $company->setForm($form->getForm());
            $company->setTaxRegime($form->getTaxRegime());
            $company->setCurrency($form->getCurrency());
            $company->setNds($form->getNds());
            $company->setDescription($form->getDescription());

            if (!$company->save()) {
                Log::error('[Company] Не удалось сохранить', [
                    'error' => DB::getQueryLog()
                ]);

                return null;
            }

            if ($form->getUuid()) {
                $this->getCache()->forget($this->getCacheKey(self::CACHE_KEY, $form->getUuid()));
            }

            return $company->refresh();
        }, 2);
    }
}
