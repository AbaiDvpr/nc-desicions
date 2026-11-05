<?php declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Cabinet\Projects\Form;


use App\DI;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\AdministrativeExpenses;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\AttractingInvestments;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\EquipmentList;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\InvestmentAmount;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\InvestPanel;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\MarketingExpenses;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\OverhaulList;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\OwnInvestment;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\PlannedCapacity;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\SalaryFund;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\SmrList;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\Turnover;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\VariableCosts;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\VariableCostsOther;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\VolumeSales;
use App\Http\Controllers\Frontend\Cabinet\Projects\Form\Fields\ReadyPremisesList;

class ProjectForm
{
    /**
     * Максимальное кол-во шагов
     */
    const MAX_STEPS = 4;

    /**
     * Элементы формы по шагам.
     * Переводы берутся из файлов локализации.
     *
     * @var array[]
     */
    protected $elements = [
        // STEP 1
        'name'       => [
            'step'   => 1,
            'type'   => 'text',
            'title'  => 'Название идеи (проекта)',
            'hint'   => null,
            'value'  => null,
            'rules'  => [
                'name.value' => 'required|max:500',
            ],
            'column' => 'col-12',
        ],
        'form'       => [
            'step'        => 1,
            'type'        => 'select',
            'title'       => 'Организационно-правовая форма',
            'placeholder' => null,
            'hint'        => null,
            'values'      => [
                1 => 'ИП',
                2 => 'ТОО',
            ],
            'value'       => 1,
            'rules'       => [
                'form.value' => 'required|in:1,2',
            ],
            'column'      => 'col-12 col-md-6',
        ],
        'nds'        => [
            'step'        => 1,
            'type'        => 'select',
            'title'       => 'С учетом НДС',
            'placeholder' => null,
            'hint'        => 'Учитывается НДС при заполнении проекта',
            'values'      => [
                1 => 'Да',
                0 => 'Нет',
            ],
            'value'       => 0,
            'rules'       => [
                'nds.value' => 'required|in:1,0',
            ],
            'column'      => 'col-12 col-md-6',
        ],
        'tax_regime' => [
            'step'        => 1,
            'type'        => 'select',
            'title'       => 'Форма налогообложения',
            'placeholder' => null,
            'hint'        => null,
            'values'      => [
                1 => 'Общеустановленный порядок',
                // 2 => 'Специальный налоговый режим',
                // 3 => 'Патент',
                4 => 'Упрощенная декларация',
                // 5 => 'Специальный налоговый режим для крестьянских и фермерских хозяйств'
            ],
            'value'       => 1,
            'rules'       => [
                'tax_regime.value' => 'required|in:1,2,3,4,5',
            ],
            'column'      => 'col-12 col-md-6',
        ],
        'currency'   => [
            'step'        => 1,
            'type'        => 'select',
            'title'       => 'В какой валюте, единице измерения произвести расчет',
            'placeholder' => null,
            'hint'        => null,
            'values'      => [
                'kzt' => 'тенге',
                'usd' => 'долл.USD',
                'eur' => 'EUR',
            ],
            'value'       => 'kzt',
            'rules'       => [
                'currency.value' => 'required|in:kzt,usd,eur',
            ],
            'column'      => 'col-12 col-md-6',
        ],
        'time_param' => [
            'step'        => 1,
            'type'        => 'select',
            'title'       => 'Временные параметры расчета',
            'placeholder' => 'Выберите один из вариантов',
            'hint'        => null,
            'values'      => [
                1 => 'по годам',
                // 2 => 'по квартально',
                3 => 'помесячно',
            ],
            'value'       => 0,
            'rules'       => [
                'time_param.value' => 'required|in:1,3',
            ],
            'column'      => 'col-12 col-md-6',
        ],
        'schedule'   => [
            'step'        => 1,
            'type'        => 'select',
            'title'       => 'График работы',
            'placeholder' => 'Выберите один из вариантов',
            'values'      => [
                1 => 'пятидневная рабочая неделя (8 часов)',  // 264 (в год) 22 дня в месяц
                2 => 'шестидневная рабочая неделя (8 часов)', // 312 (в год) 26 дня в месяц
                3 => 'ежедневная рабочая неделя (8 часов)', // 365 дней
                // 3 => 'две смены (8 часов)',
                // 4 => 'три смены (8 часов)',
            ],
            'value'       => 0,
            'rules'       => [
                'schedule.value' => 'required|in:1,2,3,4',
            ],
            'column'      => 'col-12 col-md-6',
        ],
        'planning'   => [
            'step'        => 1,
            'type'        => 'calendarRange',
            'title'       => 'Период планирования',
            'placeholder' => 'Выберите один из вариантов',
            'hint'        => 'Дата начала и окончания планирования по проекту.',
            'inputs'      => [
                'from' => 'Дата начала проекта',
                'to'   => 'Дата окончания проекта',
            ],
            'values'      => [
                'from' => null,
                'to'   => null,
            ],
            'value'       => 0,
            'rules'       => [
                'planning.values.from' => 'required',
                'planning.values.to'   => 'required',
            ],
            'column'      => 'col-12',
            'options'     => [
                'from' => [
                    'subtractYear' => 1,
                ],
                'to'   => [
                    'addYear' => 3,
                    'maxYear' => 20,
                ],
            ],
        ],

        // Turnover::FIELD_NAME => [
        //     'step'        => 1,
        //     'type'        => Turnover::FIELD_TYPE,
        //     'title'       => 'Является ли ваш проект с оборачиваемостью?',
        //     'placeholder' => 'Выберите один из вариантов',
        //     'hint'        => null,
        //     'values'      => [
        //         1 => 'Да',
        //         0 => 'Нет',
        //     ],
        //     'value'       => 0,
        //     'rules'       => Turnover::FIELD_RULES,
        //     'inputs'      => Turnover::FIELD_INPUTS,
        //     'column'      => 'col-12'
        // ],

        // STEP 2
        AttractingInvestments::FIELD_NAME => [
            'step'        => 2,
            'type'        => AttractingInvestments::FIELD_TYPE,
            'title'       => 'На каких условиях вы планируете привлечь инвестиции?',
            'placeholder' => 'при долевом финансировании привлекаемые средства инвестора ' .
                             'учитываются как взносы в уставный капитал',
            'hint'        => '',
            'inputs'      => AttractingInvestments::FIELD_INPUTS,
            'rules'       => [],
            'value'       => AttractingInvestments::EQUITY_FINANCING,
            'values'      => [
                AttractingInvestments::EQUITY_FINANCING => 'долевое финансирование (инвестор)',
                AttractingInvestments::CREDIT_FINANCING => 'долговое (кредитное) финансирование',
                AttractingInvestments::GRANT_FINANCING  => 'грантовое финансирование',
            ],
        ],
        'salesStartDate'                  => [
            'step'        => 2,
            'type'        => 'calendar',
            'title'       => 'Дата начала продаж/производства',
            'placeholder' => 'указать месяц и год начала продаж',
            'hint'        => 'Тут необходимо указать дату, когда Вы планируете начать продажи (производство)',
            'inputs'      => [],
            'values'      => [],
            'value'       => null,
            'rules'       => [
                'salesStartDate.value' => 'required|minPlanning',
            ],
            'column'      => 'col-12 col-2xl-6',
            'options'     => [
                'from' => null,
                'to'   => null,
            ],
        ],
        'plannedVolume'                   => [
            'step'        => 2,
            'type'        => 'calendar',
            'title'       => 'Срок выхода на плановый объем продаж/производства',
            'subTitle'    => 'при выполнении 100% плана',
            'placeholder' => 'выберите месяц и год',
            'hint'        => 'Тут необходимо указать дату, когда вы планируете выйти на 100% объем продаж (производства)',
            'inputs'      => [],
            'values'      => [],
            'value'       => null,
            'rules'       => [
                'plannedVolume.value' => 'required|maxPlanning',
            ],
            'column'      => 'col-12 col-2xl-6',
            'options'     => [
                'from' => null,
                'to'   => null,
            ],
        ],
        VolumeSales::FIELD_NAME           => [
            'step'          => 2,
            'type'          => VolumeSales::FIELD_TYPE,
            'title'         => 'Планируемый объем продаж в месяц (при выполнении 100% плана) количество',
            'placeholder'   => null,
            'hint'          => null,
            'inputs'        => [],
            'value'         => null,
            'values'        => [],
            'rules'         => [
                VolumeSales::FIELD_NAME . '.value' => 'required|integer|min:1',
            ],
            'defaultInput'  => VolumeSales::DEFAULT_INPUT,
            'indexEditable' => null,
        ],
        PlannedCapacity::FIELD_NAME       => [
            'step'        => 2,
            'type'        => PlannedCapacity::FIELD_TYPE,
            'title'       => 'Проектная мощность',
            'subTitle'    => null,
            'placeholder' => 'Если не указывать, то план выхода на проектную 100% мощность будет ' .
                             'равномерно распределяться на ежемесячной основе',
            'hint'        => null,
            'inputs'      => [],
            'values'      => [],
            'value'       => null,
            'rules'       => [],
            'column'      => 'col-12',
        ],
        'otherIncome'                     => [
            'step'        => 2,
            'type'        => 'numeric',
            'title'       => 'Прочие доходы (при наличии)',
            'subTitle'    => 'Укажите дополнительные доходы одной суммой, если таковые имеются',
            'placeholder' => 'сумма в месяц',
            'hint'        => null,
            'value'       => '',
            'rules'       => [
                'otherIncome.value' => 'sometimes|nullable|integer',
            ],
            'column'      => 'col-12',
        ],
        EquipmentList::FIELD_NAME         => [
            'step'         => 2,
            'type'         => EquipmentList::FIELD_TYPE,
            'title'        => 'Перечень приобретаемого оборудования (при наличии данной информации)',
            'placeholder'  => null,
            'hint'         => null,
            'inputs'       => EquipmentList::FIELD_INPUTS,
            'value'        => null,
            'values'       => [],
            'rules'        => [],
            'defaultInput' => EquipmentList::DEFAULT_INPUT,
        ],
        ReadyPremisesList::FIELD_NAME     => [
            'step'         => 2,
            'type'         => ReadyPremisesList::FIELD_TYPE,
            'title'        => 'Перечень приобретаемой недвижимости (при наличии данной информации)',
            'placeholder'  => null,
            'hint'         => null,
            'inputs'       => ReadyPremisesList::FIELD_INPUTS,
            'value'        => null,
            'values'       => [],
            'rules'        => [],
            'defaultInput' => ReadyPremisesList::DEFAULT_INPUT,
        ],
        SmrList::FIELD_NAME               => [
            'step'         => 2,
            'type'         => SmrList::FIELD_TYPE,
            'title'        => 'Перечень видов работ СМР, капитального ремонта',
            'placeholder'  => null,
            'hint'         => null,
            'inputs'       => SmrList::FIELD_INPUTS,
            'value'        => null,
            'values'       => [],
            'rules'        => [],
            'defaultInput' => SmrList::DEFAULT_INPUT,
        ],
        OverhaulList::FIELD_NAME          => [
            'step'         => 2,
            'type'         => OverhaulList::FIELD_TYPE,
            'title'        => 'Перечень материалов при СМР, капитальном ремонте',
            'placeholder'  => null,
            'hint'         => null,
            'inputs'       => OverhaulList::FIELD_INPUTS,
            'value'        => null,
            'values'       => [],
            'rules'        => [],
            'defaultInput' => OverhaulList::DEFAULT_INPUT,
        ],

        // TODO: Остается как данные по себестоимости
        // STEP 3
        VariableCosts::FIELD_NAME          => [
            'step'         => 3,
            'type'         => VariableCosts::FIELD_TYPE,
            'title'        => 'Себестоимость на единицу продукции / переменные расходы',
            'placeholder'  => 'Есть ли информация по калькуляции себестоимости ' .
                              '(переменных расходов) на единицу продукции/услуг? <br/> ' .
                              'Если да, то заполните таблицу "Себестоимость на единицу продукции". <br/> ' .
                              'Если нет, заполните таблицу "Переменные расходы" в месяц.',
            'hint'         => null,
            'inputs'       => VariableCosts::FIELD_INPUTS,
            'value'        => true,
            'values'       => [],
            'message'      => 'Заполните таблицу "Планируемый объем продаж в месяц (при выполнении 100% плана) количество" на 3 шаге',
            'defaultInput' => VariableCosts::DEFAULT_INPUT,

            VariableCostsOther::FIELD_NAME => [
                'name'         => VariableCostsOther::FIELD_NAME,
                'type'         => VariableCostsOther::FIELD_TYPE,
                'title'        => 'Переменные расходы',
                'placeholder'  => null,
                'hint'         => null,
                'inputs'       => VariableCostsOther::FIELD_INPUTS,
                'value'        => null,
                'values'       => [],
                'message'      => 'Заполните таблицу "Планируемый объем продаж в месяц (при выполнении 100% плана) количество" на 3 шаге',
                'defaultInput' => VariableCostsOther::DEFAULT_INPUT,
                'periods'      => VariableCostsOther::DEFAULT_PERIODS,
            ],
        ],
        SalaryFund::FIELD_NAME             => [
            'step'         => 3,
            'type'         => SalaryFund::FIELD_TYPE,
            'title'        => 'Фонд заработной платы',
            'placeholder'  => null,
            'hint'         => '<p>Необходимо указать штат сотрудников в проекте.</p>
                                <p><b>Процент от дохода</b> - процент от реализации товаров/услуг выбранных в поле «Товар/услуга».</p>
                                <p><b>ПП</b> - производственный персонал, работники связанные с процессом производства продукции, товаров, услуг</p>',
            'inputs'       => [],
            'value'        => null,
            'values'       => [],
            'rules'        => [],
            'defaultInput' => SalaryFund::DEFAULT_INPUT,
        ],
        'fixedCosts'                       => [
            'step'        => 3,
            'type'        => 'delimiter',
            'title'       => 'Постоянные расходы',
            'placeholder' => null,
            'value'       => null,
            'column'      => 'col-12',
        ],
        AdministrativeExpenses::FIELD_NAME => [
            'step'          => 3,
            'type'          => AdministrativeExpenses::FIELD_TYPE,
            'title'         => 'Административные расходы',
            'placeholder'   => null,
            'hint'          => 'Укажите расходы, которые Вы несете на постоянной основе в месяц',
            'inputs'        => AdministrativeExpenses::FIELD_INPUTS,
            'value'         => null,
            'values'        => [],
            'rules'         => AdministrativeExpenses::FIELD_RULES,
            'defaultInput'  => AdministrativeExpenses::DEFAULT_INPUT,
            'periods'       => AdministrativeExpenses::DEFAULT_PERIODS,
            'typesExpenses' => AdministrativeExpenses::DEFAULT_TYPES,
        ],
        MarketingExpenses::FIELD_NAME      => [
            'step'         => 3,
            'type'         => MarketingExpenses::FIELD_TYPE,
            'title'        => 'Маркетинговые расходы',
            'placeholder'  => null,
            'hint'         => 'Укажите расходы, связанные с продвижением Ваших товаров/услуг',
            'inputs'       => MarketingExpenses::FIELD_INPUTS,
            'value'        => null,
            'values'       => [],
            'rules'        => MarketingExpenses::FIELD_RULES,
            'defaultInput' => MarketingExpenses::DEFAULT_INPUT,
            'periods'      => MarketingExpenses::DEFAULT_PERIODS,
        ],

        // STEP 4
        InvestPanel::FIELD_NAME      => [
            'step'        => 4,
            'type'        => InvestPanel::FIELD_TYPE,
            'title'       => 'Блок с информацией для разделения сумм на привлекаемые или собственные средства',
            'placeholder' => null,
            'value'       => null,
            'column'      => 'col-12',
            'rules'       => [
                InvestPanel::FIELD_NAME . '.value' => 'priceSmr|priceOverhaul|priceEquipment|priceReadyPremises'
            ],
            'data'        => [],
            'distributed' => [
                'smr'            => 0,
                'overhaul'       => 0,
                'readyPremises'  => 0,
                'equipment'      => 0,
                'raw'            => 0,
                'wages'          => 0,
                'advertising'    => 0,
                'rentalExpenses' => 0,
                'otherExpenses'  => 0,
            ],
        ],
        OwnInvestment::FIELD_NAME    => [
            'step'        => 4,
            'type'        => OwnInvestment::FIELD_TYPE,
            'title'       => 'Будете ли вы участвовать собственными вложениями?',
            'placeholder' => null,
            'hint'        => 'Если да, укажите на что будут потрачены собственные средства',
            'values'      => [
                1 => 'Да',
                0 => 'Нет',
            ],
            'value'       => 0,
            'rules'       => OwnInvestment::FIELD_RULES,
            'inputs'      => OwnInvestment::FIELD_INPUTS,
            'column'      => 'col-12',
        ],
        InvestmentAmount::FIELD_NAME => [
            'step'        => 4,
            'type'        => InvestmentAmount::FIELD_TYPE,
            'title'       => 'Распределите привлекаемую сумму инвестиций по видам затрат',
            'placeholder' => 'К оборотному капиталу относятся затраты на сырье, товары, расходные материалы, ' .
                             'фонд заработной платы, рекламу, административные расходы, коммунальные расходы и прочие',
            'hint'        => 'Необходимо указать, на какие цели привлекаются инвестиции',
            'values'      => [],
            'value'       => 0,
            'rules'       => InvestmentAmount::FIELD_RULES,
            'inputs'      => InvestmentAmount::FIELD_INPUTS,
            'column'      => 'col-12',
        ],

        // 'utilityCosts' => [
        //     'step'        => 5,
        //     'type'        => 'text',
        //     'title'       => 'Коммунальные затраты при проектах в сфере производства',
        //     'placeholder' => '',
        //     'value'       => null,
        //     'rules'       => [
        //         'utilityCosts.value' => 'integer'
        //     ],
        //     'column'      => 'col-12 col-md-12'
        // ],
    ];

    /**
     * Получает нужные поля для заданного шага
     *
     * @param  null    $project
     * @param  int     $step
     * @return array[]
     */
    public function getElementsByStep($project = null, int $step = 1): array
    {
        /** @var \App\Models\Project $project */
        $elements = collect($this->elements)->where('step', $step)->map(function ($item, $key) use ($project) {
            $currentItem          = $item;
            $currentItem['title'] = __('project.' . $key);

            if ($project) {
                $currentItem['withInvest'] = DI::getRepository()->getProject()->withInvestPeriod($project->getUuid());

                if ($item['type'] === 'calendarRange') {
                    $currentItem['values'] = $project->{$key} ?? $item['values'];
                } elseif ($item['type'] === OwnInvestment::FIELD_TYPE) {
                    $currentItem['value']  = $project->{$key} ?? $item['value'];
                    $currentItem['inputs'] = (new OwnInvestment())->getFilledFields($project);
                } elseif ($item['type'] === Turnover::FIELD_TYPE) {
                    $currentItem['value']  = !is_null($project->turnover) ? 1 : 0;
                    $currentItem['inputs'] = (new Turnover())->getFilledFields($project);
                } elseif ($item['type'] === InvestmentAmount::FIELD_TYPE) {
                    $currentItem['inputs'] = (new InvestmentAmount())->getFilledFields($project);
                } elseif ($item['type'] === AttractingInvestments::FIELD_TYPE) {
                    $attractingInvestments = (new AttractingInvestments())->getFilledFields($project);
                    $currentItem['value']  = $attractingInvestments['value'];
                    $currentItem['inputs'] = $attractingInvestments['inputs'];
                } elseif ($item['type'] === 'investPanel') {
                    // Добавляем информацию о средствах которые нужно распределить
                    // на привлекаемые средства или собственное участие
                    $currentItem['data'] = (new InvestPanel())->getInfo($project);
                } elseif ($item['type'] === EquipmentList::FIELD_TYPE) {
                    $currentItem['inputs']   = $project->equipmentList;
                    // $currentItem['isActive'] = (new EquipmentList())->checkFieldActive($project);
                    // $currentItem['totalSum'] = (new EquipmentList())->getTotalSum($project);
                } elseif ($item['type'] === VolumeSales::FIELD_TYPE) {
                    $volumeSales           = DI::getRepository()->getProject()->getVolumeSales($project->getUuid())->getAll();
                    $currentItem['inputs'] = $volumeSales;
                    $currentItem['value']  = $volumeSales->count();
                } elseif ($item['type'] === SalaryFund::FIELD_TYPE) {
                    $currentItem['inputs']                             = $project->salaryFund;
                    $currentItem['value']                              = $project->salaryFund()->count();
                    $currentItem['defaultInput']['services']['values'] =
                        $project->volumeSales()->select(['id', 'name'])->get()->toArray();
                } elseif ($item['type'] === AdministrativeExpenses::FIELD_TYPE) {
                    $currentItem['inputs'] = (new AdministrativeExpenses())->getFilledFields($project);
                } elseif ($item['type'] === MarketingExpenses::FIELD_TYPE) {
                    $currentItem['inputs'] = (new MarketingExpenses())->getFilledFields($project);
                } elseif ($item['type'] === VariableCosts::FIELD_TYPE) {
                    $currentItem['inputs']                                 = (new VariableCosts())->getFilledFields($project);
                    $currentItem['value']                                  = $project->getCostType();
                    $currentItem[VariableCostsOther::FIELD_TYPE]['inputs'] = (new VariableCostsOther())->getFilledFields($project);
                } elseif ($item['type'] === ReadyPremisesList::FIELD_TYPE) {
                    $currentItem['inputs']   = $project->readyPremisesList;
                    // $currentItem['isActive'] = (new ReadyPremisesList())->checkFieldActive($project);
                    // $currentItem['totalSum'] = (new ReadyPremisesList())->getTotalSum($project);
                } elseif ($item['type'] === SmrList::FIELD_TYPE) {
                    $currentItem['inputs']   = $project->smrList;
                    // $currentItem['isActive'] = (new SmrList())->checkFieldActive($project);
                    // $currentItem['totalSum'] = (new SmrList())->getTotalSum($project);
                } elseif ($item['type'] === OverhaulList::FIELD_TYPE) {
                    $currentItem['inputs']   = $project->overhaulList;
                    // $currentItem['isActive'] = (new OverhaulList())->checkFieldActive($project);
                    // $currentItem['totalSum'] = (new OverhaulList())->getTotalSum($project);
                } elseif ($item['type'] === PlannedCapacity::FIELD_TYPE) {
                    $currentItem['inputs'] = PlannedCapacity::getInputs($project);
                } elseif ($key === 'salesStartDate' || $key === 'plannedVolume') {
                    $planning                       = $project->planning ?? [];
                    $currentItem['value']           = $project->{$key} ?? $item['value'];
                    $currentItem['options']['from'] = $planning['from'];
                    $currentItem['options']['to']   = $planning['to'];
                } else {
                    $currentItem['value'] = $project->{$key} ?? $item['value'];
                }
            }

            return $currentItem;
        });

//        if (!is_null($project) && !$project->isFixedAssets() && !$project->isWorkingCapital()) {
//            $elements = $elements->except(AttractingInvestments::FIELD_TYPE);
//        }

        return $elements->all();
    }

    /**
     * Получить кол-во шагов исходя из полей формы
     *
     * @return int
     */
    public function getCountSteps(): int
    {
        return collect($this->elements)->max('step') ?? self::MAX_STEPS;
    }

    /**
     * Получить список правил для валидации полей
     *
     * @param  int   $step
     * @return array
     */
    public function getValidateRules(int $step = 1): array
    {
        $rules  = [];
        $fields = $this->getElementsByStep(null, $step);

        foreach ($fields as $key => $field) {
            if (!empty($field['rules'])) {
                $rules = array_merge($rules, $field['rules']);
            }
        }

        return $rules;
    }

    /**
     * Получить значение элемента формы
     *
     * @param  $key
     * @param  null       $value
     * @return mixed|null
     */
    public function getElementValue($key, $value = null)
    {
        $elements = collect($this->elements)->get($key);

        if (!is_null($value)) {
            return $elements['values'][$value] ?? null;
        }

        return $elements['value'] ?? null;
    }
}
