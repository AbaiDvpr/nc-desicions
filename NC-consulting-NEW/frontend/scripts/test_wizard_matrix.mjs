#!/usr/bin/env node
/**
 * Матрица заполнения мастера финмодели: 4 шага × режимы финансирования + негативы.
 * Без UI — проверяет stepProblem / planningBounds / toPayload.
 */
import { initialForm, newAsset, newSalary, newVariableCost, newVolume } from '../src/pages/finmodel/options.js'
import {
  draftProgress,
  panelRows,
  planningBounds,
  stepAnchor,
  stepField,
  stepProblem,
  toPayload,
} from '../src/pages/finmodel/helpers.js'

function fail(msg) {
  console.error('FAIL', msg)
  process.exitCode = 1
}

function ok(msg) {
  console.log('PASS', msg)
}

function baseCompany(over = {}) {
  return {
    ...initialForm(),
    name: 'Тест проект',
    legal_form: 1,
    nds: 0,
    tax_regime: 4,
    currency: 'kzt',
    time_param: 3,
    schedule: 1,
    planning_from: '2026-12-01',
    planning_to: '2028-12-01',
    ...over,
  }
}

function withSales(form, over = {}) {
  return {
    ...form,
    sales_start: '2027-01-15',
    planned_volume: '2027-08-01',
    volume_sales: [
      {
        ...newVolume(),
        name: 'Услуга',
        count: 10,
        price: 160000,
        units: 1,
      },
    ],
    equipment: [
      {
        ...newAsset(),
        name: 'Оборудование',
        count: 1,
        price_one: 5_000_000,
        price: 5_000_000,
        year: 2027,
        life_years: 5,
        pay_month: '2027-01',
        commission_month: '2027-02',
      },
    ],
    ...over,
  }
}

function withExpenses(form, over = {}) {
  return {
    ...form,
    variable_costs: [
      {
        ...newVariableCost(),
        product: 'Услуга',
        cost_type: 'Материалы',
        price: 20000,
      },
    ],
    salary: [
      {
        ...newSalary(),
        name: 'Администратор',
        count: 1,
        salary: 250000,
      },
    ],
    admin_expenses: form.admin_expenses.map((row, index) =>
      index === 0 ? { ...row, value: 150000 } : row,
    ),
    marketing_expenses: form.marketing_expenses.map((row, index) =>
      index === 0 ? { ...row, value: 50000 } : row,
    ),
    ...over,
  }
}

/** Как InvestmentStep useEffect: распределяет свои / привлекаемые / кредит. */
function balanceInvestments(form, { ownShare = 0, creditShare = 0 } = {}) {
  const next = {
    ...form,
    own_invest: ownShare > 0 ? 1 : 0,
    own: { ...form.own },
    attracted: { ...form.attracted },
    credit: { ...form.credit },
  }
  for (const row of panelRows(next)) {
    const total = Math.round(row.total)
    const own = Math.round(total * ownShare)
    const rest = Math.max(0, total - own)
    const credit = Math.round(rest * creditShare)
    next.own[row.key] = own
    next.attracted[row.key] = rest
    next.credit[row.key] = credit
  }
  return next
}

function assertSteps(form, label) {
  for (let step = 0; step < 4; step += 1) {
    const problem = stepProblem(form, step)
    if (problem) {
      fail(`${label}: step ${step + 1} blocked: ${problem} (anchor=${stepAnchor(form, step)} field=${stepField(form, step)})`)
      return false
    }
  }
  const progress = draftProgress(form, 3)
  if (progress.blocked != null) {
    fail(`${label}: draftProgress blocked at ${progress.blocked}`)
    return false
  }
  ok(`${label}: 4/4 steps + draftProgress`)
  return true
}

function assertBlocked(form, step, includes, label) {
  const problem = stepProblem(form, step)
  if (!problem) {
    fail(`${label}: expected block on step ${step + 1}, got OK`)
    return
  }
  if (includes && !String(problem).includes(includes)) {
    fail(`${label}: expected message containing «${includes}», got «${problem}»`)
    return
  }
  ok(`${label}: blocked with «${problem}»`)
}

function assertOk(form, step, label) {
  const problem = stepProblem(form, step)
  if (problem) {
    fail(`${label}: expected OK on step ${step + 1}, got «${problem}»`)
    return
  }
  ok(`${label}: step ${step + 1} OK`)
}

// --- сценарии полного заполнения ---
const investor = balanceInvestments(
  withExpenses(
    withSales(
      baseCompany({
        financing: 1,
        investor_term: 7,
        investor_dividend: 25,
        risk_premium: 5,
        planning_to: '2027-06-01', // короче выхода на 100% — должен спасти горизонт инвестора
        planned_volume: undefined,
      }),
      { planned_volume: '2028-11-02', sales_start: '2026-12-24' },
    ),
  ),
  { ownShare: 0 },
)
// fix planned_volume override path
investor.sales_start = '2026-12-24'
investor.planned_volume = '2028-11-02'
assertSteps(investor, 'investor+short-plan+ramp-2028')
console.log('  bounds', planningBounds(investor))
console.log('  payload.planning_to', toPayload(investor).planning_to)

const credit = balanceInvestments(
  withExpenses(
    withSales(
      baseCompany({
        financing: 2,
        loan_term: 60,
        loan_grace: 6,
        loan_rate: 18,
        loan_annuity: 1,
      }),
    ),
  ),
  { ownShare: 0.2 },
)
assertSteps(credit, 'credit+20%own')

const grant = balanceInvestments(
  withExpenses(
    withSales(
      baseCompany({
        financing: 3,
      }),
    ),
  ),
  { ownShare: 0 },
)
assertSteps(grant, 'grant')

const mixed = balanceInvestments(
  withExpenses(
    withSales(
      baseCompany({
        financing: 4,
        investor_term: 5,
        investor_dividend: 30,
        risk_premium: 4,
        loan_term: 36,
        loan_grace: 3,
        loan_rate: 16,
        loan_bank_rate: 20,
        loan_subsidy_rate: 4,
        loan_subsidy_term: 24,
      }),
    ),
  ),
  { ownShare: 0.1, creditShare: 0.5 },
)
assertSteps(mixed, 'mixed investor+credit')

const yearly = balanceInvestments(
  withExpenses(
    withSales(
      baseCompany({
        financing: 1,
        time_param: 1,
        investor_term: 3,
        investor_dividend: 20,
        risk_premium: 3,
      }),
      {
        volume_sales: [{ ...newVolume(), name: 'Годовой продукт', count: 100, price: 50000, units: 1 }],
      },
    ),
  ),
)
assertSteps(yearly, 'yearly time_param')

const seasonal = balanceInvestments(
  withExpenses(
    withSales(
      baseCompany({
        financing: 1,
        investor_term: 4,
        investor_dividend: 15,
        risk_premium: 2,
      }),
      {
        volume_sales: [
          {
            ...newVolume(),
            name: 'Сезон',
            count: 20,
            price: 100000,
            seasonal: true,
            months: [6, 7, 8],
          },
        ],
      },
    ),
  ),
)
assertSteps(seasonal, 'seasonal product')

// --- негативы: понятные сообщения, совпадающие с UI ---
assertBlocked(
  withSales(baseCompany({ financing: 1, investor_term: 7, investor_dividend: 25, risk_premium: 5 }), {
    planned_volume: '',
  }),
  1,
  'Выход на 100%',
  'empty planned_volume names field',
)

assertBlocked(
  withSales(
    baseCompany({
      financing: 1,
      investor_term: 1, // горизонт почти не продлевает
      investor_dividend: 10,
      risk_premium: 1,
      planning_to: '2027-03-01',
    }),
    { sales_start: '2027-01-01', planned_volume: '2029-01-01' },
  ),
  1,
  'Выход на 100%',
  'volume beyond even extended horizon',
)

assertBlocked(
  withSales(baseCompany({ financing: 1, investor_term: 5, investor_dividend: 10, risk_premium: 1 }), {
    sales_start: '2027-06-01',
    planned_volume: '2027-03-01',
  }),
  1,
  'раньше начала продаж',
  'volume before sales_start',
)

assertBlocked(
  baseCompany({ financing: 2, loan_term: '', loan_rate: '' }),
  1,
  'кредит',
  'credit missing terms',
)

assertBlocked(
  withSales(baseCompany({ financing: 1, investor_term: 5, investor_dividend: 10, risk_premium: 1 }), {
    volume_sales: [{ ...newVolume(), name: '', count: 1, price: 1 }],
  }),
  1,
  'продукц',
  'empty product name',
)

assertBlocked(
  withExpenses(withSales(baseCompany({ financing: 3 })), {
    salary: [{ ...newSalary(), name: 'Кассир', count: '', salary: 100000 }],
  }),
  2,
  'число сотрудников',
  'incomplete salary count',
)

assertOk(
  withExpenses(withSales(baseCompany({ financing: 3 })), {
    salary: [{ ...newSalary(), name: '', count: '', salary: '' }],
  }),
  2,
  'empty salary stub allowed',
)

assertBlocked(
  withExpenses(withSales(baseCompany({ financing: 3 })), {
    variable_costs: [{ product: 'Услуга', cost_type: '', price: 10 }],
  }),
  2,
  'себестоимость',
  'incomplete cogs',
)

// Инвестиции без сведения сумм
{
  const form = withExpenses(
    withSales(
      baseCompany({
        financing: 1,
        investor_term: 5,
        investor_dividend: 10,
        risk_premium: 1,
        own_invest: 0,
      }),
    ),
  )
  // attracted пустой при ненулевом CAPEX
  const problem = stepProblem(form, 3)
  if (!problem) fail('invest unbalanced: expected block')
  else ok(`invest unbalanced: blocked with «${problem}»`)
}

// HTML-лейблы vs сообщения: тост не должен говорить «срок выхода»
{
  const form = withSales(
    baseCompany({
      financing: 1,
      investor_term: 5,
      investor_dividend: 10,
      risk_premium: 1,
      planning_to: '2027-02-01',
    }),
    { planned_volume: '2035-01-01' },
  )
  const msg = stepProblem(form, 1)
  if (/срок выхода на плановый/i.test(msg || '')) {
    fail(`legacy wording still used: ${msg}`)
  } else {
    ok(`no legacy wording: ${msg}`)
  }
}

// Нулевые значения, которые раньше проходили фронт и падали на бэке
assertBlocked(
  withSales(baseCompany({ financing: 1, investor_term: 0, investor_dividend: 10, risk_premium: 1 })),
  1,
  'срок участия',
  'investor_term=0',
)
assertBlocked(
  withSales(baseCompany({ financing: 2, loan_term: 0, loan_rate: 18 })),
  1,
  'срок кредита',
  'loan_term=0',
)
assertBlocked(
  withSales(baseCompany({ financing: 1, investor_term: 5, investor_dividend: 10, risk_premium: 1 }), {
    volume_sales: [{ ...newVolume(), name: 'A', count: 0, price: 100 }],
  }),
  1,
  'больше 0',
  'product count=0',
)
assertBlocked(
  withSales(baseCompany({ financing: 2, loan_term: 12, loan_grace: 24, loan_rate: 18 })),
  1,
  'Каникулы',
  'grace > term',
)
assertBlocked(
  withSales(baseCompany({ financing: 1, investor_term: 5, investor_dividend: 10, risk_premium: 1 }), {
    other_income: 50000,
    other_income_start: '',
  }),
  1,
  'прочих доходов',
  'other income without start',
)
assertBlocked(baseCompany({ name: '' }), 0, 'Название проекта', 'step0 names missing field')

// nds=0 должен проходить шаг 1 параметров
{
  const form = baseCompany({ nds: 0 })
  const problem = stepProblem(form, 0)
  if (problem) fail(`nds=0 should be valid, got ${problem}`)
  else ok('nds=0 valid on step 1')
}

if (!process.exitCode) console.log('\nAll wizard matrix checks passed')
else console.log('\nWizard matrix has failures')
