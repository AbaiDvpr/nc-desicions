import { INVEST_FIELDS, OPERATING_KEYS } from './options.js'

export const OTHER = '__other'

const numberFormat = new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 0 })

export const num = (value) => Number(value) || 0

export const formatMoney = (value, sign) => `${numberFormat.format(Math.round(value))} ${sign}`

export function bindFields(data, set) {
  return (key) => ({ value: data[key], onChange: (value) => set(key, value) })
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

const named = (rows) => rows.filter((row) => String(row.name || '').trim())

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
  const counts = {}
  for (const product of form.volume_sales) {
    const name = String(product.name || '').trim()
    if (!name) continue
    counts[name] = (counts[name] || 0) + num(product.count)
  }
  const raw =
    form.cost_mode === 'unit'
      ? form.variable_costs.reduce((sum, row) => sum + num(row.price) * (counts[row.product] ?? 1), 0)
      : form.variable_other.reduce((sum, row) => sum + num(row.value), 0)
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

export function capexProblem(form) {
  const targets = capexTargets(form)
  for (const key of ['smr', 'overhaul', 'ready_premises', 'equipment']) {
    const target = Math.round(targets[key])
    if (target > 0 && Math.round(distributed(form, key)) !== target) {
      return `Распределите сумму «${INVEST_LABELS[key]}»: собственные и привлекаемые вместе должны равняться ${numberFormat.format(target)}`
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
    const months = operating ? num(multipliers[key]) || 1 : 1
    return { key, label, operating, base, months, total: base * months, distributed: distributed(form, key) }
  }).filter((row) => row.base > 0)
}

export function toPayload(form) {
  const years = yearsBetween(form.planning_from, form.planning_to)
  const capacity = {}
  for (const year of years) capacity[String(year)] = num(form.planned_capacity[year])
  return {
    ...form,
    name: form.name.trim(),
    volume_sales: form.volume_sales
      .filter((row) => String(row.name || '').trim())
      .map((row) => ({ ...row, name: row.name.trim(), months: row.seasonal ? row.months : [] })),
    equipment: named(form.equipment),
    premises: named(form.premises),
    smr: named(form.smr),
    overhaul: named(form.overhaul),
    variable_costs: form.variable_costs.filter((row) => row.product || row.cost_type || row.price !== ''),
    variable_other: named(form.variable_other),
    salary: named(form.salary),
    admin_expenses: named(form.admin_expenses),
    marketing_expenses: named(form.marketing_expenses),
    planned_capacity: capacity,
  }
}
