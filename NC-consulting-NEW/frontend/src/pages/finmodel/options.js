export const STEPS = [
  {
    label: 'Параметры',
    title: 'Параметры проекта',
    description: 'Название, форма, налоги, валюта, график работы и период планирования.',
    result: 'Исходные параметры расчёта',
  },
  {
    label: 'Продажи',
    title: 'Продажи и вложения в активы',
    description:
      'Условия финансирования, объём продаж, проектная мощность, оборудование, недвижимость и строительно-монтажные работы.',
    result: 'План продаж и перечни активов',
  },
  {
    label: 'Расходы',
    title: 'Себестоимость и расходы',
    description: 'Себестоимость или переменные расходы, фонд заработной платы, административные и маркетинговые расходы.',
    result: 'Себестоимость, ФОТ и постоянные расходы',
  },
  {
    label: 'Инвестиции',
    title: 'Собственные и привлекаемые средства',
    description: 'Разделите суммы по видам затрат между собственными вложениями и привлекаемым финансированием.',
    result: 'Структура финансирования',
  },
]

export const CURRENCY_SIGNS = { kzt: '₸', usd: '$', eur: '€' }

export const LEGAL_FORMS = [
  [1, 'ИП'],
  [2, 'ТОО'],
]

export const YES_NO = [
  [1, 'Да'],
  [0, 'Нет'],
]

export const TAX_REGIMES = [
  [1, 'Общеустановленный порядок'],
  [4, 'Упрощенная декларация'],
]

export const CURRENCIES = [
  ['kzt', 'тенге'],
  ['usd', 'долл.USD'],
  ['eur', 'EUR'],
]

export const TIME_PARAMS = [
  [1, 'по годам'],
  [3, 'помесячно'],
]

export const SCHEDULES = [
  [1, 'пятидневная рабочая неделя (8 часов)'],
  [2, 'шестидневная рабочая неделя (8 часов)'],
  [3, 'ежедневная рабочая неделя (8 часов)'],
]

export const FINANCING = [
  [1, 'долевое финансирование (инвестор)'],
  [2, 'долговое (кредитное) финансирование'],
  [3, 'грантовое финансирование'],
]

export const UNITS = [
  [1, 'шт'],
  [2, 'кг'],
  [3, 'тонн'],
  [4, 'усл'],
  [5, 'м2'],
  [6, 'м3'],
  [7, 'л'],
  [8, 'м.п.'],
  [9, 'км'],
  [10, 'чел.'],
  [11, 'ед.'],
]

export const MONTH_OPTIONS = [
  [1, 'Янв'],
  [2, 'Фев'],
  [3, 'Мар'],
  [4, 'Апр'],
  [5, 'Май'],
  [6, 'Июн'],
  [7, 'Июл'],
  [8, 'Авг'],
  [9, 'Сен'],
  [10, 'Окт'],
  [11, 'Ноя'],
  [12, 'Дек'],
]

export const PERIODS = [
  [0, 'Единоразово'],
  [1, 'ежемесячно'],
  [2, 'каждые 2 месяца'],
  [3, 'ежеквартально'],
  [4, '3 раза в год'],
  [6, '2 раза в год'],
  [12, 'раз в год'],
]

export const EXPENSE_TYPES = [
  [0, 'Прочее'],
  [2, 'Аренда'],
]

export const INVEST_FIELDS = [
  ['smr', 'Перечень видов работ СМР, капитального ремонта'],
  ['overhaul', 'Перечень материалов при СМР, капитальном ремонте'],
  ['ready_premises', 'Помещения, здания, сооружения'],
  ['equipment', 'Приобретение оборудования, техники, мебели'],
  ['raw', 'Закуп сырья, товаров, расходных материалов'],
  ['advertising', 'Расходы на рекламу'],
  ['wages', 'Расходы по выплате заработной платы'],
  ['rental', 'Расходы по аренде'],
  ['other', 'Прочие расходы'],
]

export const OPERATING_KEYS = ['raw', 'wages', 'advertising', 'rental', 'other']

export const ADMIN_PRESETS = [
  ['Аренда офиса', 2],
  ['Аренда склада', 2],
  ['Аренда магазина', 2],
  ['Интернет и связь', 0],
  ['Банковское обслуживание', 0],
  ['Услуги третьих лиц (аутсорсинг)', 0],
  ['Коммунальные расходы', 0],
  ['Земельный налог', 0],
  ['Командировочные расходы', 0],
  ['Транспортные расходы', 0],
]

export const MARKETING_PRESETS = ['Контекстная реклама', 'Таргетированная реклама']

function monthShift(offset) {
  const date = new Date()
  date.setDate(1)
  date.setMonth(date.getMonth() + offset)
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`
}

export const emptyAmounts = () => ({
  smr: '',
  overhaul: '',
  ready_premises: '',
  equipment: '',
  raw: '',
  advertising: '',
  wages: '',
  rental: '',
  other: '',
})

export const newVolume = () => ({
  name: '',
  count: '',
  units: 1,
  seasonal: false,
  months: [],
  price: '',
})

export const newAsset = () => ({
  name: '',
  count: '',
  price_one: '',
  price: '',
  year: '',
  life_years: '',
})

export const newVariableCost = () => ({ product: '', cost_type: '', price: '' })

export const newExpense = (name = '', typeExpense = 0) => ({
  name,
  value: '',
  invest: false,
  period: 1,
  type_expense: typeExpense,
})

export const newSalary = () => ({
  invest: false,
  name: '',
  count: '',
  salary: '',
  percent: '',
  industrial: 0,
  services: [],
})

export function initialForm() {
  return {
    name: '',
    legal_form: 1,
    nds: 0,
    tax_regime: 1,
    currency: 'kzt',
    time_param: 3,
    schedule: 1,
    planning_from: monthShift(0),
    planning_to: monthShift(36),

    financing: 1,
    investor_term: '',
    investor_dividend: '',
    loan_term: '',
    loan_grace: '',
    loan_rate: '',
    loan_annuity: false,
    wc_term: '',
    wc_grace: '',
    wc_rate: '',

    sales_start: '',
    planned_volume: '',
    volume_sales: [newVolume()],
    planned_capacity: {},
    other_income: '',
    equipment: [],
    premises: [],
    smr: [],
    overhaul: [],

    cost_mode: 'unit',
    variable_costs: [],
    variable_other: [],
    salary: [],
    admin_expenses: ADMIN_PRESETS.map(([name, type]) => newExpense(name, type)),
    marketing_expenses: MARKETING_PRESETS.map((name) => newExpense(name)),

    own_invest: 0,
    own: emptyAmounts(),
    attracted: emptyAmounts(),
    months_raw: 1,
    months_wages: 1,
    months_advertising: 1,
    months_rental: 1,
    months_other: 1,
  }
}
