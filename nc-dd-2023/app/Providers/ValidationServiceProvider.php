<?php declare(strict_types=1);

namespace App\Providers;

use App\DI;
use App\Models\Project;
use App\Services\Payment\PaymentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class ValidationServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Validator::extend('requiredAll', function ($attribute, $value, $parameters, $validator) {
            if (isset($parameters[0])) {
                $inputs = $validator->getData()[$parameters[0]]['inputs'];

                if (count($inputs) > 0) {
                    foreach ($inputs as $input) {
                        if ($input['name'] && $input['value']) {
                            return true;
                        }
                    }
                }
            }

            return false;
        });

        // Валидатор для проверки доступности баланса
        Validator::extend('balanceAccessibly', function ($attribute, $value, $parameters, $validator) {
            $tariff         = DI::getRepository()->getModelsTariff()->getOne((int)$value);
            $paymentService = new PaymentService(Auth::user());

            return $paymentService->checkAvailableBalance($tariff->price);
        });

        $this->validDatePlaning();
        $this->hasRequiredElements();
        $this->validDatePriceReadyPremises();
        $this->validDatePriceEquipment();
        $this->validatePriceSmr();
        $this->validatePriceOverhaul();
        $this->validateArticleId();
        $this->validateAccountingType();
        $this->validateActiveType();
    }

    /**
     * Правила валидации полей salesStartDate и plannedVolume
     *
     * @return void
     */
    protected function validDatePlaning(): void
    {
        Validator::extend('minPlanning', function ($attribute, $value) {
            try {
                $project = Project::where('uuid', request()->uuid)
                    ->where('user_id', auth()->id())->whereNotNull('planning')->first();

                $planningFrom = Carbon::parse($project['planning']['from']);
                $valueFrom    = Carbon::parse($value);

                if ($valueFrom->gte($planningFrom)) {
                    return true;
                }

            } catch (\Exception $exception) {
                Log::error('[Валидация] ошибка во время валидации', [
                    'exception' => $exception
                ]);
            }

            return false;
        });

        Validator::extend('maxPlanning', function ($attribute, $value) {
            try {
                $project = Project::where('uuid', request()->uuid)
                    ->where('user_id', auth()->id())->whereNotNull('planning')->first();

                $planningFrom = Carbon::parse($project['planning']['to']);
                $valueFrom    = Carbon::parse($value);

                if ($valueFrom->lte($planningFrom)) {
                    return true;
                }

            } catch (\Exception $exception) {
                Log::error('[Валидация] ошибка во время валидации', [
                    'exception' => $exception
                ]);
            }

            return false;
        });
    }

    /**
     * Проверяет минимальное кол-во необходимых элементов
     */
    protected function hasRequiredElements(): void
    {
        Validator::extend('hasRequiredElements', function ($attribute, $value, $parameters) {
            $minRequired = isset($parameters[0]) ?? 1;

            $count = collect($value)->whereNotNull('value')->count();

            return $count >= $minRequired;
        });
    }

    /**
     * Правила валидации поля price
     *
     * @return void
     */
    protected function validDatePriceReadyPremises(): void
    {
        Validator::extend('priceReadyPremises', function () {
            try {
                $project = DI::getRepository()->getProject()->getOneByUuid(request()->uuid);

                if ($project->getUserId() !== auth()->id()) {
                    throw new \Exception('Финансовая модель не принадлежит текущему пользователю');
                }

                $ownInvestment    = request()->own_investments;
                $investmentAmount = request()->investmentAmount;
                $totalPrice       = 0;

                if ((int) $ownInvestment['value']) {
                    $totalPrice += (int) $ownInvestment['inputs'][1]['readyPremises']['value'] ?? 0;
                }

                $limitPrice = (int) $project->readyPremisesList()->sum('price');

                if ($limitPrice === 0) return true;

                $totalPrice += (int) $investmentAmount['inputs']['readyPremises']['value'] ?? 0;

                return $limitPrice === $totalPrice;
            } catch (\Exception $exception) {
                Log::error('[Валидация] ошибка во время валидации', [
                    'exception' => $exception
                ]);
            }

            return false;
        });
    }

    /**
     * Правила валидации поля price
     *
     * @return void
     */
    protected function validatePriceSmr(): void
    {
        Validator::extend('priceSmr', function () {
            try {
                $project = DI::getRepository()->getProject()->getOneByUuid(request()->uuid);

                if ($project->getUserId() !== auth()->id()) {
                    throw new \Exception('Финансовая модель не принадлежит текущему пользователю');
                }

                $ownInvestment    = request()->own_investments;
                $investmentAmount = request()->investmentAmount;
                $totalPrice       = 0;

                if ((int) $ownInvestment['value']) {
                    $totalPrice += (int) $ownInvestment['inputs'][1]['smr']['value'] ?? 0;
                }

                $limitPrice = (int) $project->smrList()->sum('price');

                if ($limitPrice === 0) return true;

                $totalPrice += (int) $investmentAmount['inputs']['smr']['value'] ?? 0;

                return $limitPrice === $totalPrice;
            } catch (\Exception $exception) {
                Log::error('[Валидация] ошибка во время валидации', [
                    'exception' => $exception
                ]);
            }

            return false;
        });
    }

    /**
     * Правила валидации при заполнении списка по капитальному ремонту
     *
     * @return void
     */
    protected function validatePriceOverhaul(): void
    {
        Validator::extend('priceOverhaul', function () {
            try {
                $project = DI::getRepository()->getProject()->getOneByUuid(request()->uuid);

                if ($project->getUserId() !== auth()->id()) {
                    throw new \Exception('Финансовая модель не принадлежит текущему пользователю');
                }

                $ownInvestment    = request()->own_investments;
                $investmentAmount = request()->investmentAmount;
                $totalPrice       = 0;

                if ((int) $ownInvestment['value']) {
                    $totalPrice += (int) $ownInvestment['inputs'][1]['overhaul']['value'] ?? 0;
                }

                $limitPrice = (int) $project->overhaulList()->sum('price');

                if ($limitPrice === 0) return true;

                $totalPrice += (int) $investmentAmount['inputs']['overhaul']['value'] ?? 0;

                return $limitPrice === $totalPrice;
            } catch (\Exception $exception) {
                Log::error('[Валидация] ошибка во время валидации', [
                    'exception' => $exception
                ]);
            }

            return false;
        });
    }

    /**
     * Правила валидации поля price
     *
     * @return void
     */
    protected function validDatePriceEquipment(): void
    {
        Validator::extend('priceEquipment', function () {
            try {
                $project = DI::getRepository()->getProject()->getOneByUuid(request()->uuid);

                if ($project->getUserId() !== auth()->id()) {
                    throw new \Exception('Финансовая модель не принадлежит текущему пользователю');
                }

                $ownInvestment    = request()->own_investments;
                $investmentAmount = request()->investmentAmount;
                $totalPrice       = 0;

                if ((int) $ownInvestment['value']) {
                    $totalPrice += (int) $ownInvestment['inputs'][1]['equipment']['value'] ?? 0;
                }

                $limitPrice = (int) $project->equipmentList()->sum('price');

                if ($limitPrice === 0) return true;

                $totalPrice += (int) $investmentAmount['inputs']['equipment']['value'] ?? 0;

                return $limitPrice === $totalPrice;
            } catch (\Exception $exception) {
                Log::error('[Валидация] ошибка во время валидации', [
                    'exception' => $exception
                ]);
            }

            return false;
        });
    }

    /**
     * Правило для валидации поля ID статьи при заполнении доходов/расходов
     *
     * @return void
     */
    protected function validateArticleId(): void
    {
        Validator::extend('articleId', function () {
            $operation = (string) request()->get('operation');

            if (in_array($operation, ['income', 'outcome'], true)) {
                $articleId     = (string) request()->get('articleId');

                $article = DI::getRepository()->getHandbook()->getArticle()->getOne($articleId);

                return !is_null($article);
            }

            return true;
        });
    }

    /**
     * Правило для валидации поля "тип расходов" в форме ежедневного учёта
     *
     * @return void
     */
    protected function validateAccountingType(): void
    {
        Validator::extend('accountingType', function () {
            $kindActivity = (int) request()->get('kindActivity');

            if (in_array($kindActivity, [1, 2, 3], true)) {
                $type = (int) request()->get('type');

                return $type > 0;
            }

            return true;
        });
    }

    /**
     * Правило для валидации поля "счёт" в форме ежедневного учёта
     *
     * @return void
     */
    protected function validateActiveType(): void
    {
        Validator::extend('activeType', function () {
            $active = (int) request()->get('active');

            if (in_array($active, [1, 2, 3, 4, 5], true)) {
                $activeType     = (string) request()->get('activeType');
                $activeTypeList = config('form-data.company.activeTypeList')[$active];

                $active = collect($activeTypeList)->firstWhere(function ($item) use ($activeType) {
                    return $item['value'] === $activeType;
                });

                return is_array($active);
            }

            return true;
        });
    }
}
