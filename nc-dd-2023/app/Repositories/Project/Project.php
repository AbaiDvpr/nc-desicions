<?php declare(strict_types=1);

namespace App\Repositories\Project;

use App\DI;
use App\Models\CoreModel;
use App\Models\Project as ProjectModel;
use App\Models\Project\Filters\PagerParams;
use App\Models\User;
use App\Repositories\BaseRepository;
use App\Repositories\Project\Objects\Fot;
use App\Repositories\Project\Objects\VolumeSales;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Репозиторий для работы с проектами
 */
class Project extends BaseRepository
{
    /**
     * Объект данных по ФОТ
     *
     * @var \App\Repositories\Project\Objects\Fot|null
     */
    protected $fot;

    /**
     * Объект для работы с объемом продаж
     *
     * @var \App\Repositories\Project\Objects\VolumeSales|null
     */
    protected $volumeSales;

    /**
     * История проекта
     *
     * @var \App\Repositories\Project\History
     */
    protected $history;

    /**
     * Получает проект по его UUID
     *
     * @param  string                           $uuid
     * @param  bool                             $useCache
     * @return \App\Models\Project|null
     */
    public function getOneByUuid(string $uuid, bool $useCache = true): ?ProjectModel
    {
        $func = function () use ($uuid) {
            return (new ProjectModel())->find($uuid);
        };

        if ($useCache) {
            return $this->getCache()->getCached(
                $this->getCacheKey($uuid),
                $func,
                mt_rand(3600, 14400),
                $this->getProjectCacheTags($uuid)
            );
        }

        return $func();
    }

    /**
     * Получение данных по Административным расходам
     *
     * @param  string                                        $uuid
     * @param  bool                                          $useCache
     * @return \Illuminate\Database\Eloquent\Collection|null
     */
    public function getAdministrativeExpenses(string $uuid, bool $useCache = true): ?Collection
    {
        $project = $this->getOneByUuid($uuid);

        if (!$project) {
            return null;
        }

        $func = function () use ($project) {
            return $project->administrativeExpenses;
        };

        if ($useCache) {
            return $this->getCache()->getCached(
                $this->getCacheKey('administrativeExpenses', $uuid),
                $func,
                mt_rand(3600, 14400),
                $this->getProjectCacheTags($uuid)
            );
        }

        return $func();
    }

    /**
     * Получение данных по Маркетинговым расходам
     *
     * @param  string                                        $uuid
     * @param  bool                                          $useCache
     * @return \Illuminate\Database\Eloquent\Collection|null
     */
    public function getMarketingExpenses(string $uuid, bool $useCache = true): ?Collection
    {
        $project = $this->getOneByUuid($uuid);

        if (!$project) {
            return null;
        }

        $func = function () use ($project) {
            return $project->marketingExpenses;
        };

        if ($useCache) {
            return $this->getCache()->getCached(
                $this->getCacheKey('marketingExpenses', $uuid),
                $func,
                mt_rand(3600, 14400),
                $this->getProjectCacheTags($uuid)
            );
        }

        return $func();
    }

    /**
     * Проверяет, подается ли проект с учетом инвест периода
     * Инвест период - это разница между датой начала планирования проекта и датой начала продаж/производства
     *
     * @param  string $uuid
     * @return bool
     */
    public function withInvestPeriod(string $uuid): bool
    {
        $project = $this->getOneByUuid($uuid);

        if (is_null($project)) {
            return false;
        }

        $planingDateFrom = $project->getPlanning()['from'] ?? null;

        if (in_array(null, [$planingDateFrom, $project->salesStartDate], true)) {
            return false;
        }

        $planingDateFrom = Carbon::parse($planingDateFrom);
        $salesStartDate  = Carbon::parse($project->salesStartDate);

        return $salesStartDate->gt($planingDateFrom);
    }

    /**
     * Получает кол-во человек Производственного Персонала (ПП)
     *
     * @param  string $uuid
     * @return int
     */
    public function getCountOfIndustrialPersonnel(string $uuid): int
    {
        $project = $this->getOneByUuid($uuid);

        if (!$project) {
            return 0;
        }

        return (int) $project->salaryFund()->where('industrial', true)->sum('count');
    }

    /**
     * Очищает кеш проекта
     *
     * @param  \App\Models\Project $project
     * @return bool
     */
    public function clearCache(ProjectModel $project): bool
    {
        $this->getHistory()->clearCache($project->user);

        return $this->getCache()->deleteByTags(
            $this->getProjectCacheTags($project->getUuid())
        );
    }

    /**
     * Сущность для работы с сотрудниками по проекту
     *
     * @param  string                                $uuid
     * @return \App\Repositories\Project\Objects\Fot
     */
    public function getFot(string $uuid): Fot
    {
        if (!$this->fot) {
            $this->fot = new Fot($this->getOneByUuid($uuid));
        }

        return $this->fot;
    }

    /**
     * Сущность для работы с историей
     *
     * @return \App\Repositories\Project\History
     */
    public function getHistory(): History
    {
        if (!$this->history) {
            $this->history = new History();
        }

        return $this->history;
    }

    /**
     * Сущность для работы с объемом продаж
     *
     * @param  string                                        $uuid
     * @return \App\Repositories\Project\Objects\VolumeSales
     */
    public function getVolumeSales(string $uuid): VolumeSales
    {
        if (!$this->volumeSales) {
            $this->volumeSales = new VolumeSales($this->getOneByUuid($uuid));
        }

        return $this->volumeSales;
    }

    /**
     * Получить список проектов пользователя
     *
     * @param  array $params
     * @return array
     */
    public function getFromParams(array $params): array
    {
        $projects = new ProjectModel();

        if (isset($params['userId'])) {
            $projects = $projects->where('user_id', (int) $params['userId']);
        }

        if (isset($params['tariffId'])) {
            $projects = $projects->where('tariff_id', (int) $params['tariffId']);
        }

        if (isset($params['name'])) {
            $projects = $projects->where('name', 'LIKE', '%'. (string) $params['name'] .'%');
        }

        if (isset($params['status'])) {
            $projects = $projects->where('status', (string) $params['status']);
        }

        if (isset($params['form'])) {
            $projects = $projects->where('form', (int) $params['form']);
        }

        if (isset($params['currency'])) {
            $projects = $projects->where('currency', (string) $params['currency']);
        }

        $records = $projects->offset((int) $params['offset'])->limit((int) $params['limit'])->latest()->get();

        return collect($records)->map(function ($record) {
            return $record->getProjectData()->getPublicData();
        })->toArray();
    }

    /**
     * Возвращает список проектов с использованием фильтра
     *
     * @param  \App\Models\Project\Filters\PagerParams     $params
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getWithPaginate(PagerParams $params): LengthAwarePaginator
    {
        $projects = new ProjectModel();

        if ($params->getUserId()) {
            $projects = $projects->where('user_id', $params->getUserId());
        }

        if ($params->getName()) {
            $projects = $projects->where('name', 'LIKE', '%' . $params->getName() . '%');
        }

        if ($params->getTariffId()) {
            $projects = $projects->where('tariff_id', $params->getTariffId());
        }

        if ($params->getStatus()) {
            $projects = $projects->where('status', $params->getStatus());
        }

        if ($params->getTaxRegime()) {
            $projects = $projects->where('tax_regime', $params->getTaxRegime());
        }

        if ($params->getFromDate()) {
            $fromDate = Carbon::parse($params->getFromDate());
            $projects = $projects->where('created_at', '>=', $fromDate);
        }

        if ($params->getToDate()) {
            $toDate   = Carbon::parse($params->getToDate());
            $projects = $projects->where('created_at', '<=', $toDate);
        }

        return $projects->latest()->paginate($params->getLimit());
    }

    /**
     * Проверяет на возможность создания проекта
     *
     * @param  \App\Models\User $user
     * @return bool
     */
    public function isPossibleToCreate(User $user): bool
    {
        if ($user->hasFullAccess()) {
            return true;
        }

        $tariff        = $user->tariff();
        $projectsCount = $this->getHistory()->getItemsCount($user);

        if ($tariff->isExpired() || $projectsCount >= $tariff->getMaxProjects()) {
            return false;
        }

        return true;
    }

    /**
     * Создает проект
     *
     * @param  \App\Models\User $user
     * @return string
     */
    public function create(User $user): string
    {
        return DB::transaction(function() use ($user) {
            $project = (new ProjectModel())->create([
                'user_id'    => $user->getAuthIdentifier(),
                'form'       => 1,
                'tax_regime' => CoreModel::TAX_REGIME_GENERAL_PROCEDURE,
                'currency'   => CoreModel::CURRENCY_KZT,
            ]);

            $this->getHistory()->create($user, $project);

            return $project->getUuid();
        }, 2);
    }

    /**
     * Возвращает ссылку на скачивание отчета
     *
     * @param  \App\Models\Project $project
     * @return string|null
     */
    public function getDownloadLink(ProjectModel $project): ?string
    {
        if (!$project->isCompleted()) {
            return null;
        }

        return route('report.get', ['uuid' => $project->getUuid()]);
    }

    /**
     * Возвращает количество проектов по статусам
     *
     * @return array
     */
    public function getCountStats(): array
    {
        $statusesCount = ProjectModel::select(DB::raw('count(*) as count, status'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        return [
            'all'         => $statusesCount->sum(),
            'byPeriod'    => $this->getCountsByPeriod(Carbon::now()->subWeek()),
            'completed'   => $statusesCount->get('completed'),
            'unCompleted' => $statusesCount->except('completed')->sum(),
        ];
    }

    /**
     * Возвращает количество проектов за период
     *
     * @param  \Carbon\Carbon $period
     * @return int
     */
    public function getCountsByPeriod(Carbon $period): int
    {
        return ProjectModel::where('created_at', '>=', $period)->count();
    }
}
