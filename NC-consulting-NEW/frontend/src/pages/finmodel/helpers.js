import { INVEST_FIELDS, OPERATING_KEYS } from './options.js'

export const OTHER = '__other'

const numberFormat = new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 2 })

export const num = (value) => Number(value) || 0

export const formatMoney = (value, sign) => `${numberFormat.format(num(value))} ${sign}`

export function bindFields(data, set) {
  return (key) => ({ value: data[key], onChange: (value) => set(key, value) })
}

/** Среднемесячный объём: сезонный count — в месяц продаж, иначе как в time_param. */
export function averageMonthlyVolume(product, monthlyTimeParam = true) {
  const count = num(product.count)
  if (!(count > 0)) return 0
  if (product.seasonal) {
    const months = (product.months || []).filter((month) => Number(month) >= 1 && Number(month) <= 12)
    const n = months.length || 0
    return (count * n) / 12
  }
  return monthlyTimeParam ? count : count / 12
}

export function averageMonthlyRevenue(form) {
  const monthly = Number(form.time_param) !== 1
  return (form.volume_sales || []).reduce((sum, row) => {
    if (!String(row.name || '').trim()) return sum
    return sum + averageMonthlyVolume(row, monthly) * num(row.price)
  }, 0)
}

export function averageMonthlyCogs(form) {
  const monthly = Number(form.time_param) !== 1
  const unit = {}
  for (const row of form.variable_costs || []) {
    const name = String(row.product || '').trim()
    if (name) unit[name] = (unit[name] || 0) + num(row.price)
  }
  if (form.cost_mode !== 'unit') {
    return (form.variable_other || []).reduce((sum, row) => sum + num(row.value), 0)
  }
  return (form.volume_sales || []).reduce((sum, row) => {
    const name = String(row.name || '').trim()
    if (!name) return sum
    return sum + averageMonthlyVolume(row, monthly) * (unit[name] || 0)
  }, 0)
}

export function fullLoadMonth(form) {
  const monthly = Number(form.time_param) !== 1
  const byName = {}
  let revenue = 0
  for (const row of form.volume_sales || []) {
    const name = String(row.name || '').trim()
    if (!name) continue
    const volume = averageMonthlyVolume(row, monthly)
    const amount = volume * num(row.price)
    revenue += amount
    byName[name] = (byName[name] || 0) + amount
  }
  if (!revenue) return null
  const unit = {}
  for (const row of form.variable_costs || []) {
    const name = String(row.product || '').trim()
    if (name) unit[name] = (unit[name] || 0) + num(row.price)
  }
  let cogs = 0
  for (const row of form.volume_sales || []) {
    const name = String(row.name || '').trim()
    if (!name) continue
    const volume = averageMonthlyVolume(row, monthly)
    cogs += (unit[name] || 0) * volume
  }
  let payroll = 0
  const staff = []
  for (const row of form.salary || []) {
    if (!String(row.name || '').trim()) continue
    payroll += num(row.count) * num(row.salary)
    staff.push(row)
  }
  const expense = (rows) =>
    (rows || []).reduce((sum, row) => sum + (String(row.name || '').trim() ? num(row.value) : 0), 0)
  const fixed = payroll + expense(form.admin_expenses) + expense(form.marketing_expenses)
  let bonus = 0
  for (const person of staff) {
    const percent = num(person.percent)
    if (!percent) continue
    const chosen = (person.services || []).map((item) => String(item).trim()).filter((name) => name in byName)
    const base = chosen.length ? chosen.reduce((sum, name) => sum + byName[name], 0) : revenue
    bonus += (percent / 100) * base
  }
  return revenue - cogs - fixed - bonus
}

function monthStamp(value) {
  const [year, month] = String(value || '')
    .slice(0, 7)
    .split('-')
    .map(Number)
  if (!year || !month) return null
  return year * 12 + month
}

function monthFromStamp(stamp) {
  const year = Math.floor((stamp - 1) / 12)
  const month = ((stamp - 1) % 12) + 1
  return `${year}-${String(month).padStart(2, '0')}`
}

/** Как в FEM: конец плана = max(анкета, выход инвестора, последний платёж кредита). */
export function planningBounds(form) {
  const from = String(form.planning_from || '').slice(0, 7)
  const asked = String(form.planning_to || '').slice(0, 7)
  if (!from || !asked) return { from, to: asked, extended: false, reasons: [] }
  const start = monthStamp(from)
  let end = monthStamp(asked)
  if (start == null || end == null) return { from, to: asked, extended: false, reasons: [] }
  if (end < start) end = start
  const reasons = []
  const mode = Number(form.financing)
  if (mode === 1 || mode === 4) {
    const years = num(form.investor_term)
    if (years > 0) {
      const exit = start + years * 12
      if (exit > end) {
        end = exit
        reasons.push(`срок инвестора ${years} лет, выход ${formatMonthRu(monthFromStamp(exit))}`)
      }
    }
  }
  if (mode === 2 || mode === 4) {
    const months = Math.max(num(form.loan_term), num(form.wc_term))
    if (months > 0) {
      const last = start + months
      if (last > end) {
        end = last
        reasons.push(`срок кредита ${months} мес., последний платёж ${formatMonthRu(monthFromStamp(last))}`)
      }
    }
  }
  return {
    from,
    to: monthFromStamp(end),
    asked,
    extended: reasons.length > 0,
    reasons,
  }
}

export function formatMonthRu(value) {
  const text = String(value || '').slice(0, 7)
  if (!/^\d{4}-\d{2}$/.test(text)) return ''
  const [year, month] = text.split('-')
  return `${month}.${year}`
}

export function horizonExtension(form) {
  const bounds = planningBounds(form)
  if (!bounds.extended) return ''
  return `Модель продлит конец плана до ${formatMonthRu(bounds.to)}: ${bounds.reasons.join('; ')}. Иначе выход или последний платёж не попадёт в расчёт.`
}

export function yearsBetween(from, to) {
  if (!from || !to) return []
  const start = Number(String(from).slice(0, 4))
  const end = Number(String(to).slice(0, 4))
  if (!start || !end || end < start) return []
  const years = []
  for (let year = start; year <= end && years.length < 21; year += 1) years.push(year)
  return years
}

const named = (rows) =>
  rows.filter((row) => String(row.name || '').trim() || Boolean(row?.land_tax) || num(row?.land_area) > 0)

function normalizeLandExpense(row) {
  const land =
    Boolean(row?.land_tax) ||
    String(row?.name || '')
      .trim()
      .toLowerCase()
      .includes('земельн') ||
    num(row?.land_area) > 0 ||
    num(row?.land_rate) > 0
  if (!land) return row
  const unit = row.land_unit === 'ha' ? 'ha' : 'm2'
  const area = num(row.land_area)
  const rate = num(row.land_rate)
  const annual = Math.round(area * rate * 1000) / 1000
  return {
    ...row,
    name: String(row.name || '').trim() || 'Земельный налог',
    land_tax: true,
    land_unit: unit,
    value: annual > 0 ? annual : row.value,
    period: row.period || 12,
    invest: false,
    type_expense: 0,
  }
}

export function listTotal(rows) {
  return named(rows).reduce((sum, row) => sum + num(row.price), 0)
}

export function capexTargets(form) {
  return {
    smr: listTotal(form.smr),
    overhaul: listTotal(form.overhaul),
    ready_premises: listTotal(form.premises),
    equipment: listTotal(form.equipment),
  }
}

export function operatingMonthly(form) {
  // raw — средняя себестоимость в месяц (сезонные не суммируются как «каждый месяц»).
  const raw = averageMonthlyCogs(form)
  const wages = form.salary.reduce((sum, row) => sum + num(row.salary) * num(row.count), 0)
  const advertising = form.marketing_expenses.reduce((sum, row) => sum + num(row.value), 0)
  const rental = form.admin_expenses
    .filter((row) => Number(row.type_expense) === 2)
    .reduce((sum, row) => sum + num(row.value), 0)
  const other = form.admin_expenses
    .filter((row) => Number(row.type_expense) !== 2)
    .reduce((sum, row) => sum + num(row.value), 0)
  return { raw, wages, advertising, rental, other }
}

export function distributed(form, key) {
  const own = Number(form.own_invest) === 1 ? num(form.own[key]) : 0
  return own + num(form.attracted[key])
}

const INVEST_LABELS = Object.fromEntries(INVEST_FIELDS)

function filled(value) {
  if (typeof value === 'string') return value.trim() !== ''
  return value !== null && value !== undefined
}

/** Число > 0: пусто, 0 и отрицательные не считаются заполненными. */
function positive(value) {
  if (!filled(value) && value !== 0) return false
  const amount = num(value)
  return Number.isFinite(amount) && amount > 0
}

function monthInRange(value, from, to) {
  if (!filled(value) || !filled(from) || !filled(to)) return false
  const month = String(value).slice(0, 7)
  return month >= String(from).slice(0, 7) && month <= String(to).slice(0, 7)
}

/** Понятная ошибка по дате: пусто / раньше старта / позже конца плана. */
export function dateRangeIssue(value, from, to, label) {
  if (!filled(from) || !filled(to)) return 'Сначала укажите период планирования на шаге 1'
  if (!filled(value)) return `Укажите дату «${label}»`
  const month = String(value).slice(0, 7)
  const start = String(from).slice(0, 7)
  const end = String(to).slice(0, 7)
  if (month < start) {
    // Частый кейс: дата продаж заполнена, но раньше «Начала проекта» — старый toast «Укажите дату» врал.
    return `«${label}» (${formatMonthRu(month)}) раньше начала проекта (${formatMonthRu(start)}). На шаге 1 сдвиньте «Начало проекта» на ${formatMonthRu(month)} или раньше — либо сдвиньте эту дату`
  }
  if (month > end) {
    return `«${label}» (${formatMonthRu(month)}) позже конца плана (${formatMonthRu(end)}). Продлите окончание проекта на шаге 1`
  }
  return ''
}

function negativeCapacity(values) {
  return values.some((value) => value !== '' && value !== null && value !== undefined && num(value) < 0)
}

const STEP0_LABELS = {
  name: 'Название проекта',
  legal_form: 'Форма бизнеса',
  nds: 'Плательщик НДС',
  tax_regime: 'Налоговый режим',
  currency: 'Валюта',
  time_param: 'Периодичность расчёта',
  schedule: 'График работы',
  planning_from: 'Начало проекта',
  planning_to: 'Окончание проекта',
}

function stepIssue(form, index) {
  if (index === 0) {
    const keys = Object.keys(STEP0_LABELS)
    // nds может быть 0 («Нет») — это валидно
    const absent = keys.find((key) => {
      if (key === 'nds') return form[key] === '' || form[key] === null || form[key] === undefined
      return !filled(form[key])
    })
    if (absent) {
      return {
        message: `Заполните поле «${STEP0_LABELS[absent]}»`,
        anchor: '',
        field: absent,
      }
    }
    if (String(form.planning_to).slice(0, 7) < String(form.planning_from).slice(0, 7)) {
      return { message: 'Окончание проекта раньше начала', anchor: '', field: 'planning_to' }
    }
    const regime = Number(form.tax_regime)
    if (regime === 4 && !filled(form.tax_simplified_rate) && form.tax_simplified_rate !== 0) {
      return { message: 'Укажите ставку упрощёнки', anchor: '', field: 'tax_simplified_rate' }
    }
    if (regime === 1 && !filled(form.tax_cit_rate) && form.tax_cit_rate !== 0) {
      return { message: 'Укажите ставку налога на прибыль / ИПН режима', anchor: '', field: 'tax_cit_rate' }
    }
    if (regime === 1 && Number(form.nds) === 1 && !filled(form.vat_rate) && form.vat_rate !== 0) {
      return { message: 'Укажите ставку НДС', anchor: '', field: 'vat_rate' }
    }
    if (!filled(form.vosms_rate) && form.vosms_rate !== 0) {
      return { message: 'Укажите ставку ВОСМС', anchor: '', field: 'vosms_rate' }
    }
    if (Number(form.legal_form) === 2 && regime === 1 && !filled(form.social_tax_rate) && form.social_tax_rate !== 0) {
      return { message: 'Укажите ставку социального налога', anchor: '', field: 'social_tax_rate' }
    }
    for (const key of ['tax_simplified_rate', 'tax_cit_rate', 'vat_rate', 'vosms_rate', 'social_tax_rate']) {
      if (filled(form[key]) && num(form[key]) > 100) {
        return { message: 'Ставка не больше 100%', anchor: '', field: key }
      }
    }
    return null
  }
  if (index === 1) {
    const financing = Number(form.financing)
    const needsInvestor = financing === 1 || financing === 4
    const needsLoan = financing === 2 || financing === 4
    const bounds = planningBounds(form)
    if (needsInvestor) {
      if (!positive(form.investor_term)) {
        return { message: 'Укажите срок участия инвестора (от 1 года)', anchor: 'finance', field: 'investor_term' }
      }
      if (!filled(form.investor_dividend) && form.investor_dividend !== 0) {
        return { message: 'Укажите дивиденды инвестора', anchor: 'finance', field: 'investor_dividend' }
      }
      if (!filled(form.risk_premium) && form.risk_premium !== 0) {
        return { message: 'Укажите премию за риск проекта', anchor: 'finance', field: 'risk_premium' }
      }
      if (num(form.investor_term) > 100) {
        return { message: 'Срок участия инвестора — не больше 100 лет', anchor: 'finance', field: 'investor_term' }
      }
      if (num(form.investor_dividend) > 100) {
        return { message: 'Дивиденды инвестора — не больше 100%', anchor: 'finance', field: 'investor_dividend' }
      }
      if (num(form.risk_premium) > 100) {
        return { message: 'Премия за риск — не больше 100%', anchor: 'finance', field: 'risk_premium' }
      }
    }
    if (needsLoan) {
      if (!positive(form.loan_term)) {
        return { message: 'Укажите срок кредита (месяцы)', anchor: 'finance', field: 'loan_term' }
      }
      if (!filled(form.loan_rate) && form.loan_rate !== 0) {
        return { message: 'Укажите ставку кредита', anchor: 'finance', field: 'loan_rate' }
      }
      if (num(form.loan_term) > 250) {
        return { message: 'Срок кредита — не больше 250 месяцев', anchor: 'finance', field: 'loan_term' }
      }
      if (num(form.loan_grace) > num(form.loan_term)) {
        return { message: 'Каникулы не могут быть больше срока кредита', anchor: 'finance', field: 'loan_grace' }
      }
      if (num(form.loan_grace) > 36) {
        return { message: 'Каникулы — не больше 36 месяцев', anchor: 'finance', field: 'loan_grace' }
      }
      if (num(form.loan_rate) > 100) {
        return { message: 'Ставка кредита — не больше 100%', anchor: 'finance', field: 'loan_rate' }
      }
      if (positive(form.wc_term) && (num(form.wc_term) < 24 || num(form.wc_term) > 36)) {
        return { message: 'Срок оборотных средств — от 24 до 36 месяцев', anchor: 'finance', field: 'wc_term' }
      }
      if (positive(form.wc_term) && num(form.wc_grace) > num(form.wc_term)) {
        return { message: 'Каникулы по оборотным не могут быть больше срока', anchor: 'finance', field: 'wc_grace' }
      }
      if (positive(form.loan_subsidy_term) && num(form.loan_subsidy_term) > num(form.loan_term)) {
        return { message: 'Срок субсидии не может быть больше срока кредита', anchor: 'finance', field: 'loan_subsidy_term' }
      }
    }
    const program = programIssue(form)
    if (program) return { message: program, anchor: 'finance', field: '' }
    const salesIssue = dateRangeIssue(form.sales_start, bounds.from, bounds.to, 'Начало продаж')
    if (salesIssue) return { message: salesIssue, anchor: 'dates', field: 'sales_start' }
    const volumeIssue = dateRangeIssue(form.planned_volume, bounds.from, bounds.to, 'Выход на 100%')
    if (volumeIssue) return { message: volumeIssue, anchor: 'dates', field: 'planned_volume' }
    if (String(form.planned_volume).slice(0, 7) < String(form.sales_start).slice(0, 7)) {
      return {
        message: '«Выход на 100%» не может быть раньше начала продаж',
        anchor: 'dates',
        field: 'planned_volume',
      }
    }
    const products = form.volume_sales || []
    if (products.length === 0) {
      return { message: 'Добавьте хотя бы одну продукцию', anchor: 'products', field: '' }
    }
    if (products.some((row) => !filled(row.name))) {
      return { message: 'Укажите название продукции', anchor: 'products', field: '' }
    }
    if (products.some((row) => !positive(row.count))) {
      return { message: 'Количество продукции должно быть больше 0', anchor: 'products', field: '' }
    }
    if (products.some((row) => !positive(row.price))) {
      return { message: 'Цена продукции должна быть больше 0', anchor: 'products', field: '' }
    }
    if (
      products.some(
        (row) => filled(row.sales_start) && !monthInRange(row.sales_start, bounds.from, bounds.to),
      )
    ) {
      return {
        message: 'Дата начала продаж продукта должна входить в период планирования',
        anchor: 'products',
        field: '',
      }
    }
    if (products.some((product) => product.own_capacity && negativeCapacity(Object.values(product.planned_capacity || {})))) {
      return { message: 'Загрузка не может быть отрицательной', anchor: 'products', field: '' }
    }
    if (negativeCapacity(Object.values(form.planned_capacity || {}))) {
      return { message: 'Загрузка не может быть отрицательной', anchor: 'capacity', field: '' }
    }
    if (num(form.other_income) > 0 && !filled(form.other_income_start)) {
      return {
        message: 'Укажите дату начала прочих доходов',
        anchor: 'income',
        field: 'other_income_start',
      }
    }
    if (filled(form.other_income_start)) {
      const otherIssue = dateRangeIssue(form.other_income_start, bounds.from, bounds.to, 'Начало прочих доходов')
      if (otherIssue) return { message: otherIssue, anchor: 'income', field: 'other_income_start' }
    }
    const assets = assetsIssue(form)
    if (assets) return assets
    return null
  }
  if (index === 2) {
    const brokenCost = (form.variable_costs || []).some(
      (row) =>
        (filled(row.product) || filled(row.cost_type) || filled(row.price) || row.price === 0) &&
        (!filled(row.product) || !filled(row.cost_type) || (!filled(row.price) && row.price !== 0)),
    )
    if (brokenCost) return { message: 'Заполните себестоимость на единицу: продукт, тип и цену', anchor: 'cogs', field: '' }
    // Пустые строки не блокируют (как себестоимость). Начатая строка — должность, кол-во и оклад > 0.
    for (const row of form.salary || []) {
      const started =
        filled(row.name) || positive(row.count) || positive(row.salary) || filled(row.percent)
      if (!started) continue
      if (!filled(row.name)) {
        return { message: 'Укажите должность в ФОТ', anchor: 'salary', field: '' }
      }
      if (!positive(row.count)) {
        return {
          message: `Укажите число сотрудников для «${String(row.name).trim()}» (больше 0)`,
          anchor: 'salary',
          field: '',
        }
      }
      if (!positive(row.salary)) {
        return {
          message: `Укажите оклад для «${String(row.name).trim()}» (больше 0)`,
          anchor: 'salary',
          field: '',
        }
      }
    }
    return null
  }
  if (index === 3) {
    const program = programIssue(form)
    if (program) return { message: program, anchor: '', field: '' }
    if (form.own_invest === '' || form.own_invest === null || form.own_invest === undefined) {
      return { message: 'Укажите, есть ли собственные вложения', anchor: '', field: 'own_invest' }
    }
    const assets = assetsIssue(form)
    if (assets) return assets
    const problem = capexProblem(form)
    return problem ? { message: problem, anchor: '', field: '' } : null
  }
  return null
}

const ASSET_LISTS = [
  ['equipment', 'Оборудование'],
  ['premises', 'Недвижимость'],
  ['smr', 'Работы'],
  ['overhaul', 'Материалы'],
]

/** Каждая добавленная строка актива должна быть заполнена до «Далее». */
function assetsIssue(form) {
  const bounds = planningBounds(form)
  for (const [key, label] of ASSET_LISTS) {
    const rows = form[key] || []
    for (let index = 0; index < rows.length; index += 1) {
      const row = rows[index]
      const title = String(row.name || '').trim()
      const missing = []
      if (!title) missing.push('название')
      if (!positive(row.count)) missing.push('количество')
      const hasPrice = positive(row.price) || (positive(row.price_one) && positive(row.count))
      if (row.already_owned) {
        if (!positive(row.book_value) && !hasPrice) missing.push('балансовую стоимость')
      } else if (!hasPrice) {
        missing.push('стоимость')
      }
      if (!positive(row.year) || num(row.year) < 1900) missing.push('год')
      if (!filled(row.life_years) && row.life_years !== 0) {
        missing.push('срок службы')
      } else if (num(row.life_years) < 0) {
        missing.push('срок службы')
      }
      if (!row.already_owned && !filled(row.pay_month)) missing.push('дату оплаты')
      if (!filled(row.commission_month)) missing.push('дату ввода')
      if (missing.length) {
        const who = title ? `«${title}»` : `строку ${index + 1}`
        return {
          message: `Вы забыли заполнить по активам (${label}): для ${who} укажите ${missing.join(', ')}`,
          anchor: 'assets',
          field: '',
          assetTab: key,
        }
      }
      if (!row.already_owned && filled(row.pay_month)) {
        const payIssue = dateRangeIssue(row.pay_month, bounds.from, bounds.to, 'Дата оплаты')
        if (payIssue) {
          return {
            message: `Активы (${label}), ${title ? `«${title}»` : `строка ${index + 1}`}: ${payIssue}`,
            anchor: 'assets',
            field: '',
            assetTab: key,
          }
        }
      }
      if (filled(row.commission_month)) {
        const commissionIssue = dateRangeIssue(row.commission_month, bounds.from, bounds.to, 'Дата ввода')
        if (commissionIssue) {
          return {
            message: `Активы (${label}), ${title ? `«${title}»` : `строка ${index + 1}`}: ${commissionIssue}`,
            anchor: 'assets',
            field: '',
            assetTab: key,
          }
        }
      }
    }
  }
  return null
}

function activeProgram(form) {
  const financing = Number(form.financing)
  if (financing !== 2 && financing !== 4) return null
  const program = form.gov_program
  if (!program || typeof program !== 'object' || !program.slug) return null
  return program
}

export function asText(value) {
  return String(value ?? '').replace(',', '.').trim()
}

function stillTyping(value, target) {
  const text = asText(value)
  if (!text) return true
  return asText(target).startsWith(text)
}

export function programTitle(title) {
  const text = String(title || '').trim()
  if (!text) return '«программе»'
  return text.startsWith('«') ? text : `«${text}»`
}

/** Держит банк / субсидию Damu / ставку клиента согласованными: банк − субсидия = клиент. */
export function syncGovLoanRates(form, patch = {}, prefer = 'client') {
  const next = { ...form, ...patch }
  const bankText = asText(next.loan_bank_rate)
  const subsidyText = asText(next.loan_subsidy_rate)
  const clientText = asText(next.loan_rate)
  const bank = num(next.loan_bank_rate)
  const subsidy = num(next.loan_subsidy_rate)
  const client = num(next.loan_rate)
  const round3 = (value) => Math.max(0, Math.round(value * 1000) / 1000)
  if (bankText !== '' && bank > 0) {
    if (prefer === 'subsidy' && subsidyText !== '') {
      next.loan_rate = round3(bank - subsidy)
    } else if (prefer === 'client' && clientText !== '') {
      next.loan_subsidy_rate = round3(bank - client)
    } else if (subsidyText !== '') {
      next.loan_rate = round3(bank - subsidy)
    } else if (clientText !== '') {
      next.loan_subsidy_rate = round3(bank - client)
    }
  }
  return next
}

function termOutside(value, low, high) {
  if (asText(value) === '') return false
  const term = Number(value)
  if (high && term > high) return true
  return Boolean(low && term < low && !stillTyping(term, low))
}

export function programIssue(form) {
  const program = activeProgram(form)
  if (!program) return ''
  const title = program.title || 'госпрограмме'
  if (program.allows_investment === false) {
    return `${programTitle(title)} не покрывает кредит на помещение и оборудование. Работайте без госпрограммы.`
  }
  if (program.rate != null && asText(form.loan_rate) !== '' && Math.abs(num(form.loan_rate) - Number(program.rate)) > 0.001) {
    return `Ставка для клиента должна быть ${program.rate}% — так в программе ${programTitle(title)}`
  }
  if (asText(form.loan_bank_rate) === '' && !form.loan_nbk_plus_4) {
    return `Укажите ставку банка или включите «НБ РК + 4%» — иначе график Damu не сойдётся с Радугой.`
  }
  if (program.term_kind === 'subsidy' && asText(form.loan_subsidy_term) === '') {
    return `Укажите срок субсидии по программе ${programTitle(title)}`
  }
  if (asText(form.loan_bank_rate) !== '' && asText(form.loan_rate) !== '' && num(form.loan_bank_rate) + 0.001 < num(form.loan_rate)) {
    return 'Ставка для клиента не может быть выше ставки банка'
  }
  if (program.term_kind === 'loan') {
    const term = Number(form.loan_term)
    const low = Number(program.term_min_months) || 0
    const high = Number(program.term_max_months) || 0
    if (term && low && term < low) return `Срок ${term} мес. меньше минимума программы ${programTitle(title)}: ${low} мес.`
    if (term && high && term > high) return `Срок ${term} мес. больше максимума программы ${programTitle(title)}: ${high} мес.`
  }
  const graceMax = Number(program.grace_max_months) || 0
  if (graceMax && Number(form.loan_grace) > graceMax) {
    return `Каникулы больше ${graceMax} мес. — это предел программы ${programTitle(title)}.`
  }
  if (program.allows_wc !== false && program.term_kind === 'subsidy' && program.rate != null && Number(form.wc_term)) {
    if (Math.abs(num(form.wc_rate) - Number(program.rate)) > 0.001) {
      return `Ставка по оборотным средствам должна быть ${program.rate}% — так в программе ${programTitle(title)}`
    }
  }
  return ''
}

export function programBreak(form) {
  const program = activeProgram(form)
  if (!program) return ''
  const title = program.title || 'программе'
  if (program.rate != null && asText(form.loan_rate) !== '' && !stillTyping(form.loan_rate, program.rate) && Math.abs(num(form.loan_rate) - Number(program.rate)) > 0.001) {
    return `Ставка больше не из программы ${programTitle(title)}. Программа снята, заполните ставку заново, без госпрограммы.`
  }
  if (program.term_kind === 'loan' && termOutside(form.loan_term, Number(program.term_min_months) || 0, Number(program.term_max_months) || 0)) {
    return `Срок вышел за пределы ${programTitle(title)}. Программа снята, ставки очищены: заполните их заново, без госпрограммы.`
  }
  const graceMax = Number(program.grace_max_months) || 0
  if (graceMax && asText(form.loan_grace) !== '' && Number(form.loan_grace) > graceMax) {
    return `Каникулы больше ${graceMax} мес., это предел ${programTitle(title)}. Программа снята, ставки очищены: заполните их заново, без госпрограммы.`
  }
  if (program.allows_wc !== false && program.term_kind === 'subsidy' && program.rate != null && num(form.wc_term) && asText(form.wc_rate) !== '' && !stillTyping(form.wc_rate, program.rate) && Math.abs(num(form.wc_rate) - Number(program.rate)) > 0.001) {
    return `Ставка оборотных средств больше не из программы ${programTitle(title)}. Программа снята, заполните ставки заново, без госпрограммы.`
  }
  return ''
}

export function alignProgram(form) {
  const program = activeProgram(form)
  if (!program) return null
  const title = program.title || 'госпрограмме'
  const patch = {}
  const bits = []
  if (program.rate != null && asText(form.loan_rate) !== '') {
    const rate = Number(program.rate)
    if (Math.abs(num(form.loan_rate) - rate) > 0.001 && !stillTyping(form.loan_rate, rate)) {
      patch.loan_rate = rate
      bits.push(`ставка ${rate}%`)
    }
  }
  if (program.term_kind === 'loan' && asText(form.loan_term) !== '') {
    const term = Number(form.loan_term)
    const low = Number(program.term_min_months) || 0
    const high = Number(program.term_max_months) || 0
    if (high && term > high) {
      patch.loan_term = high
      bits.push(`срок не больше ${high} мес.`)
    } else if (low && term < low && !stillTyping(term, low)) {
      patch.loan_term = low
      bits.push(`срок не меньше ${low} мес.`)
    }
  }
  if (program.term_kind === 'subsidy' && program.rate != null && Number(form.wc_term) && asText(form.wc_rate) !== '') {
    const rate = Number(program.rate)
    if (Math.abs(num(form.wc_rate) - rate) > 0.001 && !stillTyping(form.wc_rate, rate)) {
      patch.wc_rate = rate
      bits.push(`ставка оборотных средств ${rate}%`)
    }
  }
  if (!bits.length) return null
  return { patch, message: `По программе ${programTitle(title)}: ${bits.join(', ')}` }
}

const CAPEX_KEYS = ['smr', 'overhaul', 'ready_premises', 'equipment']

/** Суммы кредита как в backend `_loans`: CAPEX + operating; оборотный транш при wc_term. */
export function creditSummary(form) {
  const financing = Number(form.financing)
  if (financing !== 2 && financing !== 4) {
    return { applicable: false, total: 0, investment: 0, working: 0 }
  }
  const withOwn = Number(form.own_invest) === 1
  let capex = 0
  let operating = 0
  for (const row of panelRows(form)) {
    const own = withOwn ? num(form.own?.[row.key]) : 0
    const attracted = Math.max(0, Math.round(row.total) - Math.round(own))
    const debt = financing === 4 ? Math.min(num(form.credit?.[row.key]), attracted) : attracted
    if (CAPEX_KEYS.includes(row.key)) capex += debt
    else if (OPERATING_KEYS.includes(row.key)) operating += debt
  }
  if (capex === 0 && operating === 0) {
    for (const key of CAPEX_KEYS) {
      const attracted = num(form.attracted?.[key])
      capex += financing === 4 ? Math.min(num(form.credit?.[key]), attracted) : attracted
    }
    for (const key of OPERATING_KEYS) {
      const attracted = num(form.attracted?.[key])
      operating += financing === 4 ? Math.min(num(form.credit?.[key]), attracted) : attracted
    }
  }
  const program = activeProgram(form)
  const allowWc = !(program && program.allows_wc === false)
  let investment = 0
  let working = 0
  if (allowWc && num(form.wc_term) > 0 && operating > 0) {
    working = operating
    investment = capex
  } else if (allowWc) {
    investment = capex + operating
  } else {
    investment = capex
  }
  return { applicable: true, total: investment + working, investment, working }
}

export function investmentDebt(form) {
  return creditSummary(form).investment
}

/** Срок субсидии = срок кредита, но не больше максимума программы (если он есть). */
export function suggestedSubsidyTerm(form, program = activeProgram(form)) {
  const loanTerm = Math.max(0, Math.round(num(form.loan_term)))
  if (!loanTerm) return ''
  const cap = Number(program?.term_max_months) || 0
  return cap > 0 ? Math.min(loanTerm, cap) : loanTerm
}

/**
 * Предпросмотр графика — та же логика %, что лист «График платежей» в FEM:
 * при ставке банка и сроке субсидии 0 клиент платит банк; иначе клиент до срока субсидии, потом банк.
 * Аннуитетный взнос считается от ставки банка (как Damu/Радуга).
 */
export function loanSketch(form) {
  const client = num(form.loan_rate)
  const bank = num(form.loan_bank_rate)
  const dual = bank > 0
  const subsidyTerm = Math.max(0, Math.round(num(form.loan_subsidy_term)))
  const term = Math.max(0, Math.round(num(form.loan_term)))
  const grace = Math.min(term, Math.max(0, Math.round(num(form.loan_grace))))
  const annuity = Boolean(form.loan_annuity)
  const summary = creditSummary(form)
  const principal = summary.investment > 0 ? summary.investment : summary.total
  // Что клиент реально платит в льготный период (или всегда, если субсидии нет).
  const clientPayAnnual = dual && subsidyTerm <= 0 ? bank : client || bank
  const bankAnnual = dual ? bank : clientPayAnnual
  const bankMonthly = bankAnnual / 100 / 12
  const clientMonthly = (dual ? client : clientPayAnnual) / 100 / 12
  const paying = Math.max(0, term - grace)
  let payment = 0
  if (principal > 0 && paying > 0) {
    payment = bankMonthly === 0
      ? principal / paying
      : (principal * bankMonthly * (1 + bankMonthly) ** paying) / ((1 + bankMonthly) ** paying - 1)
  }
  const marks = []
  if (principal > 0 && term > 0) {
    const wanted = new Set([grace > 0 ? grace : 1, term])
    if (paying > 0) wanted.add(Math.min(term, grace + 1))
    if (paying > 2) wanted.add(Math.min(term, grace + Math.ceil(paying / 2)))
    let balance = principal
    for (let month = 1; month <= term; month += 1) {
      const bankInterest = balance * bankMonthly
      const underSubsidy = dual && subsidyTerm > 0 && month <= subsidyTerm
      const interest = dual ? (underSubsidy ? balance * clientMonthly : bankInterest) : balance * clientMonthly
      let body = 0
      if (month <= grace) body = month === term ? balance : 0
      else if (month === term) body = balance
      else if (annuity) body = Math.min(balance, Math.max(0, payment - bankInterest))
      else body = Math.min(balance, principal / paying)
      if (wanted.has(month)) {
        marks.push({
          month,
          due: body + interest,
          interest,
          body,
          share: body + interest > 0 ? interest / (body + interest) : 0,
          left: balance - body,
          grace: month <= grace && month !== term,
        })
      }
      balance -= body
    }
  }
  return {
    annual: clientPayAnnual,
    bankAnnual,
    monthly: clientPayAnnual / 12,
    principal,
    term,
    grace,
    paying,
    annuity,
    payment,
    marks,
    subsidyTerm,
    dual,
    subsidyInactive: dual && subsidyTerm <= 0,
  }
}

export function stepProblem(form, index) {
  return stepIssue(form, index)?.message || ''
}

export function stepAnchor(form, index) {
  return stepIssue(form, index)?.anchor || ''
}

export function stepField(form, index) {
  return stepIssue(form, index)?.field || ''
}

export function stepAssetTab(form, index) {
  return stepIssue(form, index)?.assetTab || ''
}

export function draftProgress(form, reached) {
  let complete = 0
  const last = Math.max(0, Math.min(reached, 3))
  for (let index = 0; index <= last; index += 1) {
    if (stepProblem(form, index)) return { complete, blocked: index }
    complete = index + 1
  }
  return { complete, blocked: null }
}

export function capexProblem(form) {
  for (const row of panelRows(form)) {
    const target = Math.round(row.total)
    if (Number(form.financing) === 4) {
      const credit = num(form.credit?.[row.key])
      const room = target - (Number(form.own_invest) === 1 ? num(form.own[row.key]) : 0)
      if (Math.round(credit) > Math.round(Math.max(0, room))) {
        return `Кредит по «${row.label}» больше остатка ${numberFormat.format(Math.max(0, room))}`
      }
    }
    if (target > 0 && Math.round(row.distributed) !== target) {
      if (Math.round(num(form.own[row.key])) > target) {
        return `Свои вложения по «${row.label}» больше нужной суммы ${numberFormat.format(target)}`
      }
      return `Сумма «${row.label}» ещё не сходится: свои и привлекаемые вместе должны равняться ${numberFormat.format(target)}`
    }
  }
  return ''
}

export function panelRows(form) {
  const capex = capexTargets(form)
  const monthly = operatingMonthly(form)
  const multipliers = {
    raw: form.months_raw,
    wages: form.months_wages,
    advertising: form.months_advertising,
    rental: form.months_rental,
    other: form.months_other,
  }
  return INVEST_FIELDS.map(([key, label]) => {
    const operating = OPERATING_KEYS.includes(key)
    const base = operating ? monthly[key] : capex[key]
    const rawMonths = multipliers[key]
    const months = operating ? (rawMonths === '' || rawMonths === null || rawMonths === undefined ? 1 : Math.max(0, num(rawMonths))) : 1
    return { key, label, operating, base, months, total: base * months, distributed: distributed(form, key) }
  }).filter((row) => row.base > 0)
}

function capacityOf(source) {
  const capacity = {}
  for (const [year, value] of Object.entries(source || {})) {
    if (value === '' || value === null || value === undefined) continue
    capacity[String(year)] = num(value)
  }
  return capacity
}

function monthOf(value) {
  const text = String(value || '')
  return text.length >= 7 ? text.slice(0, 7) : text
}

export function toPayload(form) {
  const bounds = planningBounds(form)
  const planningTo = bounds.to || monthOf(form.planning_to)
  const years = yearsBetween(form.planning_from, planningTo)
  const capacity = {}
  for (const year of years) {
    const value = form.planned_capacity[year]
    if (value === '' || value === null || value === undefined) continue
    capacity[String(year)] = num(value)
  }
  return {
    ...form,
    name: form.name.trim(),
    planning_from: monthOf(form.planning_from),
    // Сохраняем эффективный горизонт (инвестор/кредит), как считает FEM.
    planning_to: monthOf(planningTo),
    sales_start: monthOf(form.sales_start),
    planned_volume: monthOf(form.planned_volume),
    volume_sales: form.volume_sales
      .filter((row) => String(row.name || '').trim())
      .map((row) => ({
        ...row,
        name: row.name.trim(),
        months: row.seasonal ? row.months : [],
        sales_start: row.sales_start ? monthOf(row.sales_start) : '',
        own_capacity: Boolean(row.own_capacity),
        planned_capacity: row.own_capacity ? capacityOf(row.planned_capacity) : {},
      })),
    equipment: named(form.equipment),
    premises: named(form.premises),
    smr: named(form.smr),
    overhaul: named(form.overhaul),
    variable_costs: form.variable_costs.filter((row) => row.product || row.cost_type || row.price !== ''),
    variable_other: named(form.variable_other),
    salary: named(form.salary),
    admin_expenses: named(form.admin_expenses).map((row) => normalizeLandExpense(row)),
    marketing_expenses: named(form.marketing_expenses),
    planned_capacity: capacity,
    other_income_start: form.other_income_start ? monthOf(form.other_income_start) : '',
    other_income_period: [1, 2, 3].includes(Number(form.other_income_period)) ? Number(form.other_income_period) : 1,
  }
}
