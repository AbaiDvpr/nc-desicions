import { Children, useEffect, useState } from 'react'
import { api } from '../../api.js'
import { DateField } from '../../components/DateField.jsx'
import { Dropdown } from '../../components/Dropdown.jsx'
import {
  Checkbox,
  Choice,
  Toggle,
  Field,
  MonthChips,
  NumberInput,
  TextInput,
} from './fields.jsx'
import {
  alignProgram,
  asText,
  averageMonthlyRevenue,
  bindFields,
  creditSummary,
  dateRangeIssue,
  formatMoney,
  fullLoadMonth,
  horizonExtension,
  loanSketch,
  num,
  investDetailLines,
  panelRows,
  planningBounds,
  programBreak,
  programTitle,
  suggestedSubsidyTerm,
  syncGovLoanRates,
  yearsBetween,
  fullLoadAtStart,
  zeroCogsState,
} from './helpers.js'
import {
  CURRENCY_SIGNS,
  EXPENSE_TYPES,
  INVEST_FIELDS,
  LEGAL_FORMS,
  MONTH_OPTIONS,
  OTHER_INCOME_PERIODS,
  PERIODS,
  UNITS,
  YES_NO,
  newAsset,
  newInventory,
  DEFAULT_LIFE_YEARS,
  LIFE_YEAR_HINTS,
  isLandTaxName,
  isLandTaxRow,
  LAND_AREA_UNITS,
  newExpense,
  newSalary,
  newVariableCost,
  newVolume,
} from './options.js'

function RemoveButton({ onClick }) {
  return (
    <button type="button" className="fm-icon-btn" onClick={onClick} aria-label="Удалить">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3" />
      </svg>
    </button>
  )
}

function ProductList({ items, onChange, sign, monthly, years, dateMin, dateMax }) {
  const patch = (index, partial) => onChange(items.map((item, i) => (i === index ? { ...item, ...partial } : item)))
  const hasSeasonal = items.some((item) => item.seasonal && (item.months || []).length)
  // Сумма в тех же единицах, что и поле объёма: помесячно → месяц, по годам → год.
  // Раньше averageMonthlyRevenue всегда давал месяц, а подпись при time_param=год была «за год».
  const turnover = hasSeasonal
    ? averageMonthlyRevenue({ volume_sales: items, time_param: monthly ? 3 : 1 })
    : items.reduce((sum, row) => {
        if (!String(row.name || '').trim()) return sum
        return sum + num(row.count) * num(row.price)
      }, 0)
  const period = hasSeasonal ? 'в месяц в среднем' : monthly ? 'в месяц' : 'за год'
  const linePeriod = monthly ? 'в месяц' : 'за год'

  return (
    <div className="sales-list">
      {items.length > 0 && (
        <p className="sales-sum">
          {items.length} {plural(items.length, 'позиция', 'позиции', 'позиций')}
          {' · '}
          при 100% загрузки <strong>{formatMoney(turnover, sign)}</strong> {period}
        </p>
      )}
      <ShowMore id="products">
      {items.map((item, index) => (
        <article key={index} className="sales-card sales-card--compact">
          <div className="sales-line">
            <Field label="Продукция">
              <TextInput
                required
                value={item.name}
                placeholder="Название"
                onChange={(v) => patch(index, { name: v })}
              />
            </Field>
            <Field label={monthly ? 'В месяц (при 100%)' : 'За год (при 100%)'}>
              <NumberInput required value={item.count} placeholder="0" onChange={(v) => patch(index, { count: v })} />
            </Field>
            <Field label="Ед.">
              <Dropdown value={item.units} onChange={(v) => patch(index, { units: v })} options={UNITS} />
            </Field>
            <Field label="Цена">
              <NumberInput required value={item.price} suffix={sign} placeholder="0" onChange={(v) => patch(index, { price: v })} />
            </Field>
            <div className="sales-line__end">
              <RemoveButton onClick={() => onChange(items.filter((_, i) => i !== index))} />
            </div>
          </div>
          <div className="sales-line__total">
            Итого при 100%: <strong>{formatMoney(num(item.count) * num(item.price), sign)}</strong> {linePeriod}
          </div>
          <details className="sales-more">
            <summary>Сезон, дата и своя загрузка</summary>
            <div className="sales-card__foot">
              <button
                type="button"
                className={`sales-link ${item.seasonal ? 'sales-link--on' : ''}`}
                onClick={() => patch(index, { seasonal: !item.seasonal })}
              >
                {item.seasonal ? 'Продаётся в выбранные месяцы' : 'Круглый год'}
              </button>
            </div>
            {item.seasonal && (
              <div className="sales-card__season">
                <MonthChips value={item.months} onChange={(v) => patch(index, { months: v })} options={MONTH_OPTIONS} />
              </div>
            )}
            <div className="sales-card__grid">
              <Field label="Начало продаж" hint="пусто — как у проекта; раньше старта проекта выбрать нельзя">
                <DateField
                  value={item.sales_start || ''}
                  min={dateMin}
                  max={dateMax}
                  onChange={(v) => patch(index, { sales_start: v })}
                />
              </Field>
            </div>
            <div className="sales-card__foot">
              <button
                type="button"
                className={`sales-link ${item.own_capacity ? 'sales-link--on' : ''}`}
                onClick={() => patch(index, { own_capacity: !item.own_capacity })}
              >
                {item.own_capacity ? 'Своя загрузка по годам' : 'Загрузка как у проекта'}
              </button>
            </div>
            {item.own_capacity && years.length > 0 && (
              <div className="sales-years">
                {years.map((year) => (
                  <Field key={year} label={String(year)}>
                    <NumberInput
                      max={1000}
                      suffix="%"
                      value={item.planned_capacity?.[year] ?? ''}
                      onChange={(v) => patch(index, { planned_capacity: { ...(item.planned_capacity || {}), [year]: v } })}
                      placeholder="авто"
                    />
                  </Field>
                ))}
              </div>
            )}
          </details>
        </article>
      ))}
      </ShowMore>
      <button
        type="button"
        className="btn btn--small fm-list__add"
        onClick={() => {
          onChange([...items, newVolume()])
          window.dispatchEvent(new CustomEvent('fm-show-panel', { detail: 'products' }))
        }}
      >
        Добавить продукцию
      </button>
    </div>
  )
}

function AssetCards({ items, onChange, sign, projectStart, category = 'equipment' }) {
  const patch = (index, partial) => onChange(items.map((item, i) => (i === index ? { ...item, ...partial } : item)))
  const total = items.reduce((sum, item) => sum + num(item.price), 0)
  const startMonth = String(projectStart || '').slice(0, 7)
  const startYear = startMonth.slice(0, 4)
  const lifeHint = LIFE_YEAR_HINTS[category] || 'Срок амортизации по этой позиции'
  const lifePlaceholder = String(DEFAULT_LIFE_YEARS[category] || 5)
  const shortLifeOnPremises =
    category === 'premises' && items.some((item) => String(item.name || '').trim() && num(item.life_years) > 0 && num(item.life_years) < 15)

  return (
    <div className="sales-list">
      {items.length > 0 && (
        <p className="sales-sum">
          {items.length} {plural(items.length, 'позиция', 'позиции', 'позиций')} · <strong>{formatMoney(total, sign)}</strong>
        </p>
      )}
      {items.length === 0 && <div className="fm-list__empty">Пока пусто. Добавьте строку, если такие затраты есть.</div>}
      {shortLifeOnPremises ? (
        <p className="setup-warning">
          У помещения срок меньше 15 лет — к концу прогноза оно почти обнулится, прибыль занизится. Для зданий обычно 20–25 лет.
        </p>
      ) : null}
      <ShowMore id="assets">
      {items.map((item, index) => (
        <article key={index} className="sales-card">
          <div className="sales-card__title">
            <TextInput
              required
              value={item.name}
              placeholder="Название"
              onChange={(v) => patch(index, { name: v })}
            />
            <RemoveButton onClick={() => onChange(items.filter((_, i) => i !== index))} />
          </div>
          <div className="sales-card__grid sales-card__grid--asset-money">
            <Field label="Кол-во" required>
              <NumberInput
                required
                integer
                value={item.count}
                placeholder="1"
                onChange={(v) => patch(index, { count: v, price: num(v) * num(item.price_one) })}
              />
            </Field>
            <Field label="Цена за ед.">
              <NumberInput
                value={item.price_one}
                suffix={sign}
                placeholder="0"
                onChange={(v) => patch(index, { price_one: v, price: num(item.count) * num(v) })}
              />
            </Field>
            <Field
              label={item.already_owned ? 'Исходная стоимость' : 'Стоимость'}
              required={!item.already_owned}
              hint={item.already_owned ? 'Справочно; в ДДС не платится' : ''}
            >
              <NumberInput
                required={!item.already_owned}
                value={item.price}
                suffix={sign}
                placeholder="0"
                onChange={(v) => patch(index, { price: v })}
              />
            </Field>
            <Field label="Год" required hint={startYear ? `Старт проекта: ${startYear}` : ''}>
              <NumberInput
                required
                integer
                group={false}
                value={item.year}
                placeholder={startYear || '2027'}
                onChange={(v) => patch(index, { year: v })}
              />
            </Field>
            <Field label="Срок, лет" required hint={`${lifeHint}. 0 — без капитализации`}>
              <NumberInput
                required
                integer
                value={item.life_years}
                placeholder={lifePlaceholder}
                onChange={(v) => patch(index, { life_years: v })}
              />
            </Field>
          </div>
          <div
            className={`sales-card__grid sales-card__grid--asset-dates${
              item.already_owned ? ' sales-card__grid--asset-dates-owned' : ''
            }`}
          >
            <Field label="Уже куплен" group>
              <Toggle
                value={Boolean(item.already_owned)}
                onChange={(v) => {
                  if (v) {
                    patch(index, {
                      already_owned: true,
                      book_value: item.book_value || item.price || '',
                      commission_month: item.commission_month || startMonth || item.pay_month || '',
                    })
                    return
                  }
                  patch(index, { already_owned: false })
                }}
                label="До начала проекта"
              />
            </Field>
            {item.already_owned ? (
              <Field
                label="Балансовая стоимость"
                required
                hint="Остаток на балансе сейчас; с неё амортизация. Если равна исходной — оставьте как есть"
              >
                <NumberInput
                  required
                  value={item.book_value}
                  suffix={sign}
                  placeholder={item.price || '0'}
                  onChange={(v) => patch(index, { book_value: v })}
                />
              </Field>
            ) : (
              <Field label="Дата оплаты" required hint="Когда уйдёт платёж в ДДС">
                <DateField
                  required
                  value={item.pay_month ? `${String(item.pay_month).slice(0, 7)}-01` : ''}
                  min={startMonth || undefined}
                  onChange={(v) => patch(index, { pay_month: v ? String(v).slice(0, 7) : '' })}
                />
              </Field>
            )}
            <Field
              label="Дата ввода"
              required
              hint={
                item.already_owned
                  ? 'С какого месяца в модели идёт амортизация (обычно старт проекта)'
                  : 'С неё идёт амортизация'
              }
            >
              <DateField
                required
                value={item.commission_month ? `${String(item.commission_month).slice(0, 7)}-01` : ''}
                min={startMonth || undefined}
                onChange={(v) => {
                  const month = v ? String(v).slice(0, 7) : ''
                  patch(index, {
                    commission_month: month,
                    ...(item.already_owned || item.pay_month ? {} : { pay_month: month }),
                  })
                }}
              />
            </Field>
          </div>
        </article>
      ))}
      </ShowMore>
      <button
        type="button"
        className="btn btn--small fm-list__add"
        onClick={() => {
          onChange([...items, newAsset({ start: startMonth, category })])
          window.dispatchEvent(new CustomEvent('fm-show-panel', { detail: 'assets' }))
        }}
      >
        Добавить строку
      </button>
    </div>
  )
}

const ASSET_TABS = [
  ['equipment', 'Оборудование'],
  ['premises', 'Недвижимость'],
  ['smr', 'Работы'],
  ['overhaul', 'Материалы'],
  ['inventory', 'Товарный запас'],
]

/** Первоначальный товарный запас: без амортизации, оплата один раз через ДДС U34. */
function InventoryCards({ items, onChange, sign }) {
  const patch = (index, partial) => onChange(items.map((item, i) => (i === index ? { ...item, ...partial } : item)))
  const total = items.reduce((sum, item) => sum + num(item.price), 0)

  return (
    <div className="sales-list">
      {items.length > 0 && (
        <p className="sales-sum">
          {items.length} {plural(items.length, 'позиция', 'позиции', 'позиций')} · <strong>{formatMoney(total, sign)}</strong>
          {' · '}
          без амортизации, в ДДС как первоначальный запас
        </p>
      )}
      {items.length === 0 && (
        <div className="fm-list__empty">
          Первый закуп товара и сырья — сюда. Не в «Материалы»: там амортизация и повторная оплата в CAPEX.
        </div>
      )}
      <ShowMore id="inventory">
        {items.map((item, index) => (
          <article key={index} className="sales-card sales-card--compact">
            <div className="sales-line">
              <Field label="Наименование">
                <TextInput
                  required
                  value={item.name}
                  placeholder="Товар / сырьё"
                  onChange={(v) => patch(index, { name: v })}
                />
              </Field>
              <Field label="Кол-во" required>
                <NumberInput
                  required
                  integer
                  value={item.count}
                  placeholder="1"
                  onChange={(v) => patch(index, { count: v, price: num(v) * num(item.price_one) })}
                />
              </Field>
              <Field label="Цена за ед.">
                <NumberInput
                  value={item.price_one}
                  suffix={sign}
                  placeholder="0"
                  onChange={(v) => patch(index, { price_one: v, price: num(item.count) * num(v) })}
                />
              </Field>
              <Field label="Стоимость" required>
                <NumberInput
                  required
                  value={item.price}
                  suffix={sign}
                  placeholder="0"
                  onChange={(v) => patch(index, { price: v })}
                />
              </Field>
              <div className="sales-line__end">
                <RemoveButton onClick={() => onChange(items.filter((_, i) => i !== index))} />
              </div>
            </div>
          </article>
        ))}
      </ShowMore>
      <button
        type="button"
        className="btn btn--small fm-list__add"
        onClick={() => {
          onChange([...items, newInventory()])
          window.dispatchEvent(new CustomEvent('fm-show-panel', { detail: 'assets' }))
        }}
      >
        Добавить позицию запаса
      </button>
    </div>
  )
}

const FINANCING_CHOICES = [
  [1, 'Инвестор'],
  [2, 'Кредит'],
  [3, 'Грант'],
  [4, 'Инвестор и кредит'],
]

function CostCards({ items, onChange, sign, products }) {
  const patch = (index, partial) => onChange(items.map((item, i) => (i === index ? { ...item, ...partial } : item)))
  return (
    <div className="sales-list">
      {items.length === 0 && <div className="fm-list__empty">Пока нет затрат на единицу.</div>}
      <ShowMore id="cogs">
      {items.map((item, index) => (
        <article key={index} className="sales-card">
          <div className="sales-card__title">
            <TextInput required value={item.cost_type} placeholder="Вид затрат" onChange={(v) => patch(index, { cost_type: v })} />
            <RemoveButton onClick={() => onChange(items.filter((_, i) => i !== index))} />
          </div>
          <div className="sales-card__grid sales-card__grid--expense">
            <Field label="Продукция">
              <Dropdown required value={item.product} onChange={(v) => patch(index, { product: v })} options={products} placeholder="Выберите" />
            </Field>
            <Field label="На единицу">
              <NumberInput required value={item.price} suffix={sign} placeholder="0" onChange={(v) => patch(index, { price: v })} />
            </Field>
          </div>
        </article>
      ))}
      </ShowMore>
      <button
        type="button"
        className="btn btn--small fm-list__add"
        onClick={() => {
          onChange([...items, newVariableCost()])
          window.dispatchEvent(new CustomEvent('fm-show-panel', { detail: 'cogs' }))
        }}
      >
        Добавить затрату
      </button>
    </div>
  )
}

function landAreaUnit(item) {
  return item?.land_unit === 'ha' ? 'ha' : 'm2'
}

function landAreaLabel(unit) {
  return unit === 'ha' ? 'га' : 'м²'
}

function roundLand(value) {
  if (!Number.isFinite(value) || value === 0) return ''
  const rounded = Math.round(value * 1e6) / 1e6
  return String(rounded)
}

function ExpenseCards({ items, onChange, sign, withType, landMode = false, id = 'fixed' }) {
  const patch = (index, partial) => onChange(items.map((item, i) => (i === index ? { ...item, ...partial } : item)))
  const total = items.reduce((sum, item) => sum + num(item.value), 0)
  const setLandTax = (index, item, area, rate, unit = landAreaUnit(item)) => {
    const annual = Math.round(num(area) * num(rate) * 1000) / 1000
    patch(index, {
      land_tax: true,
      land_unit: unit,
      land_area: area,
      land_rate: rate,
      value: annual > 0 ? annual : item.value,
      period: 12,
      invest: false,
      type_expense: 0,
    })
  }
  const setLandUnit = (index, item, nextUnit) => {
    const prev = landAreaUnit(item)
    const unit = nextUnit === 'ha' ? 'ha' : 'm2'
    if (prev === unit) return
    let area = num(item.land_area)
    let rate = num(item.land_rate)
    if (area > 0 || rate > 0) {
      if (prev === 'm2' && unit === 'ha') {
        area /= 10000
        rate *= 10000
      } else {
        area *= 10000
        rate /= 10000
      }
    }
    setLandTax(index, item, roundLand(area), roundLand(rate), unit)
  }
  return (
    <div className="sales-list">
      {items.length > 0 && (
        <p className="sales-sum">
          {items.length} {plural(items.length, 'строка', 'строки', 'строк')} · <strong>{formatMoney(total, sign)}</strong>
        </p>
      )}
      {items.length === 0 && landMode ? (
        <div className="fm-list__empty">Пока нет участков. Добавьте строку — сумма считается как площадь × ставка в год.</div>
      ) : null}
      <ShowMore id={id}>
      {items.map((item, index) => {
        const landTax = landMode || isLandTaxRow(item)
        const unit = landAreaUnit(item)
        const unitLabel = landAreaLabel(unit)
        return (
        <article key={index} className="sales-card">
          <div className="sales-card__title">
            <TextInput
              value={item.name}
              placeholder={landMode ? 'Участок / земельный налог' : 'Название расхода'}
              onChange={(v) => {
                const next = { name: v }
                if (landMode) {
                  next.land_tax = true
                  next.period = 12
                  next.invest = false
                  next.type_expense = 0
                } else if (isLandTaxName(v) && !isLandTaxRow(item)) {
                  next.land_tax = true
                  next.period = 12
                  next.invest = false
                  next.type_expense = 0
                }
                patch(index, next)
              }}
            />
            <RemoveButton onClick={() => onChange(items.filter((_, i) => i !== index))} />
          </div>
          {landTax ? (
            <div className="sales-card__grid sales-card__grid--land-tax">
              <Field label="Единица" hint="Площадь и ставка в одной мере">
                <Dropdown value={unit} onChange={(v) => setLandUnit(index, item, v)} options={LAND_AREA_UNITS} />
              </Field>
              <Field label="Площадь" hint={`Участок в ${unitLabel}`}>
                <NumberInput
                  value={item.land_area}
                  suffix={unitLabel}
                  placeholder="0"
                  onChange={(v) => setLandTax(index, item, v, item.land_rate)}
                />
              </Field>
              <Field label="Ставка" hint={`${sign} за ${unitLabel} в год — своя по участку`}>
                <NumberInput
                  value={item.land_rate}
                  suffix={`${sign}/${unitLabel}`}
                  placeholder="0"
                  onChange={(v) => setLandTax(index, item, item.land_area, v)}
                />
              </Field>
              <Field label="Сумма в год" hint="Площадь × ставка; в модели — раз в год">
                <NumberInput
                  value={item.value}
                  suffix={sign}
                  placeholder="0"
                  onChange={(v) => patch(index, { value: v, period: 12, invest: false, land_tax: true })}
                />
              </Field>
              <Field label="Как часто">
                <Dropdown value={item.period || 12} onChange={(v) => patch(index, { period: v, land_tax: true })} options={PERIODS} />
              </Field>
            </div>
          ) : (
            <div className={`sales-card__grid ${withType ? 'sales-card__grid--expense-type' : 'sales-card__grid--expense'}`}>
              <Field label="Сумма">
                <NumberInput value={item.value} suffix={sign} placeholder="0" onChange={(v) => patch(index, { value: v })} />
              </Field>
              <Field label="Как часто">
                <Dropdown value={item.period} onChange={(v) => patch(index, { period: v })} options={PERIODS} />
              </Field>
              {withType && (
                <Field label="Тип">
                  <Dropdown value={item.type_expense} onChange={(v) => patch(index, { type_expense: v })} options={EXPENSE_TYPES} />
                </Field>
              )}
              <Field
                label="С начала проекта"
                hint="Вкл. — платит и до открытия, и после (в каждом году — свои месяцы). Выкл. — только с даты начала продаж."
              >
                <Toggle value={item.invest} onChange={(v) => patch(index, { invest: v })} />
              </Field>
            </div>
          )}
          {landTax ? (
            <p className="fm-field__hint">
              Земельный налог: площадь ({unitLabel}) × ставка ({sign}/{unitLabel}) = сумма в год. Название можно менять — строка останется здесь.
            </p>
          ) : null}
        </article>
        )
      })}
      </ShowMore>
      <button
        type="button"
        className="btn btn--small fm-list__add"
        onClick={() => {
          onChange([...items, landMode ? newExpense('Земельный налог', 0, { land: true }) : newExpense()])
          window.dispatchEvent(new CustomEvent('fm-show-panel', { detail: id }))
        }}
      >
        {landMode ? 'Добавить участок' : 'Добавить расход'}
      </button>
    </div>
  )
}

const STAFF_KINDS = [
  [1, 'Производственный'],
  [0, 'Административный'],
]

const STAFF_TAX_ROWS = [
  { exempt: 'opv_exempt', rate: 'opv_rate', label: 'ОПВ работника не удерживается', fallback: 10 },
  { exempt: 'vosms_exempt', rate: 'vosms_rate', label: 'ВОСМС работника не удерживается', fallback: 2 },
  { exempt: 'so_exempt', rate: 'so_rate', label: 'СО не начисляется', fallback: 5 },
  { exempt: 'opvr_exempt', rate: 'opvr_rate', label: 'ОПВР не начисляется', fallback: 3.5 },
  { exempt: 'osms_exempt', rate: 'osms_rate', label: 'ООСМС не начисляется', fallback: 3 },
]

function StaffCards({ items, onChange, sign, form, set, rates }) {
  const patch = (index, partial) => onChange(items.map((item, i) => (i === index ? { ...item, ...partial } : item)))
  return (
    <div className="sales-list">
      {items.length === 0 && <div className="fm-list__empty">Штат пока не указан.</div>}
      <ShowMore id="salary">
      {items.map((item, index) => (
        <article key={index} className="sales-card">
          <div className="sales-card__grid sales-card__grid--staff">
            <Field label="Должность" required>
              <TextInput
                required
                value={item.name}
                placeholder="Должность"
                onChange={(v) => patch(index, { name: v })}
              />
            </Field>
            <Field label="Кол-во" required>
              <NumberInput required value={item.count} placeholder="1" onChange={(v) => patch(index, { count: v })} />
            </Field>
            <Field label="Оклад (до налогов)" required>
              <NumberInput required value={item.salary} suffix={sign} placeholder="0" onChange={(v) => patch(index, { salary: v })} />
            </Field>
            <Field label="% от дохода">
              <NumberInput max={100} value={item.percent} suffix="%" placeholder="0" onChange={(v) => patch(index, { percent: v })} />
            </Field>
            <RemoveButton onClick={() => onChange(items.filter((_, i) => i !== index))} />
          </div>
          <div className="staff-kind">
            <Field
              label="Тип персонала"
              required
              hint="Производственный — делает продукт или услугу, ФОТ идёт в себестоимость. Административный — управление и поддержка, ФОТ в общих расходах"
            >
              <Choice
                value={item.industrial}
                onChange={(v) => patch(index, { industrial: Number(v) })}
                options={STAFF_KINDS}
                pair
                required
              />
            </Field>
          </div>
          <details className="sales-more">
            <summary>Налоги и запуск</summary>
            <div className="sales-card__staff-extra">
              <div className="staff-tax-flags">
                <Toggle value={item.invest} onChange={(v) => patch(index, { invest: v })} label="С начала проекта (и до продаж)" />
              </div>
              <div className="staff-tax-list">
                {STAFF_TAX_ROWS.map((row) => (
                  <div key={row.exempt} className="staff-tax-row">
                    <Toggle
                      value={Boolean(item[row.exempt])}
                      onChange={(v) => patch(index, { [row.exempt]: v })}
                      label={row.label}
                    />
                    <div className="staff-tax-rate" title="Ставка для всего проекта">
                      <NumberInput
                        max={100}
                        value={form[row.rate]}
                        suffix="%"
                        placeholder={String(rates[row.rate] ?? row.fallback)}
                        onChange={(v) => set(row.rate, v)}
                        aria-label={`Ставка ${row.rate}`}
                      />
                    </div>
                  </div>
                ))}
              </div>
              <span className="fm-field__hint">Оклад — начисление до удержаний. ОПВ и ВОСМС удерживаются из зарплаты к выплате; СО, ОПВР, ООСМС и соцналог (ТОО на ОУР) — за счёт работодателя. Процент общий для проекта; тумблер — только для этой должности. ОПВР: процент — для 2026 года, дальше по закону: 2027 — {rates.opvr_rate_2027 ?? 4.5}%, с 2028 — {rates.opvr_rate_2028 ?? 5}%.</span>
            </div>
          </details>
        </article>
      ))}
      </ShowMore>
      <button
        type="button"
        className="btn btn--small fm-list__add"
        onClick={() => {
          onChange([...items, newSalary()])
          window.dispatchEvent(new CustomEvent('fm-show-panel', { detail: 'salary' }))
        }}
      >
        Добавить должность
      </button>
    </div>
  )
}

const EXPENSE_TABS = [
  ['admin_expenses', 'Административные'],
  ['marketing_expenses', 'Маркетинг'],
  ['land_tax', 'Земельный налог'],
]

const CURRENCY_CHOICES = [
  ['kzt', '₸ Тенге (KZT)'],
  ['usd', '$ Доллар (USD)'],
  ['eur', '€ Евро (EUR)'],
]

const SCHEDULE_CHOICES = [
  [1, '5 дней в неделю'],
  [2, '6 дней в неделю'],
  [3, 'Каждый день'],
]

const TAX_CHOICES = [
  [1, 'Общий'],
  [4, 'Упрощёнка'],
]

const PERIOD_CHOICES = [
  [3, 'Помесячно'],
  [1, 'По годам'],
]

function plural(value, one, few, many) {
  const n = Math.abs(value) % 100
  const n1 = n % 10
  if (n > 10 && n < 20) return many
  if (n1 > 1 && n1 < 5) return few
  if (n1 === 1) return one
  return many
}

function ShowMore({ id, children, limit = 3 }) {
  const items = Children.toArray(children)
  const [open, setOpen] = useState(false)
  const extra = items.length - limit

  useEffect(() => {
    if (!id) return undefined
    const show = (event) => {
      if (event.detail === id) setOpen(true)
    }
    window.addEventListener('fm-show-panel', show)
    return () => window.removeEventListener('fm-show-panel', show)
  }, [id])

  return (
    <>
      {open || extra <= 0 ? items : items.slice(0, limit)}
      {extra > 0 && (
        <button type="button" className="sales-reveal" onClick={() => setOpen((value) => !value)}>
          {open ? 'Скрыть' : `Показать ещё ${extra}`}
        </button>
      )}
    </>
  )
}

function planningMonths(from, to) {
  if (!from || !to || to < from) return null
  const [fromYear, fromMonth] = from.split('-').map(Number)
  const [toYear, toMonth] = to.split('-').map(Number)
  const months = (toYear - fromYear) * 12 + (toMonth - fromMonth) + 1
  if (!Number.isFinite(months) || months <= 0) return null
  return months
}

function RowIcon({ children }) {
  return (
    <span className="setup-row__icon">
      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
        {children}
      </svg>
    </span>
  )
}

function useHighlightField() {
  const [field, setField] = useState('')
  useEffect(() => {
    let timer = 0
    const onHighlight = (event) => {
      const name = String(event.detail || '')
      setField(name)
      window.clearTimeout(timer)
      if (!name) return
      timer = window.setTimeout(() => setField(''), 4500)
    }
    window.addEventListener('fm-highlight-field', onHighlight)
    return () => {
      window.clearTimeout(timer)
      window.removeEventListener('fm-highlight-field', onHighlight)
    }
  }, [])
  return field
}

function SetupRow({ icon, title, text, children, className = '', section, alert = false }) {
  return (
    <section className={`setup-row ${alert ? 'setup-row--alert' : ''} ${className}`.trim()} data-section={section}>
      <div className="setup-row__intro">
        {icon}
        <div>
          <h3>{title}</h3>
          <p>{text}</p>
        </div>
      </div>
      <div className="setup-row__body">{children}</div>
    </section>
  )
}

export function CompanyStep({ form, set, rateDefaults }) {
  const f = bindFields(form, set)
  const months = planningMonths(form.planning_from, form.planning_to)
  const regime = Number(form.tax_regime)
  const too = Number(form.legal_form) === 2
  const vatOn = regime === 1 && Number(form.nds) === 1
  const rates = rateDefaults || {}

  const simplifiedDefault = too
    ? (rates.simplified_too_rate ?? rates.simplified_rate ?? 4)
    : (rates.simplified_ip_rate ?? rates.simplified_rate ?? 4)

  const setTaxRegime = (value) => {
    set('tax_regime', value)
    // Подставить дефолт ставки режима, если поле ещё пустое.
    if (Number(value) === 4 && (form.tax_simplified_rate === '' || form.tax_simplified_rate == null)) {
      set('tax_simplified_rate', simplifiedDefault)
    }
    if (Number(value) === 1 && (form.tax_cit_rate === '' || form.tax_cit_rate == null)) {
      set('tax_cit_rate', too ? (rates.cit_too_rate ?? 20) : (rates.cit_ip_rate ?? 10))
    }
  }

  const setLegal = (value) => {
    const nextToo = Number(value) === 2
    set('legal_form', value)
    if (regime === 1 && (form.tax_cit_rate === '' || form.tax_cit_rate == null)) {
      set('tax_cit_rate', nextToo ? (rates.cit_too_rate ?? 20) : (rates.cit_ip_rate ?? 10))
    }
    if (regime === 4 && (form.tax_simplified_rate === '' || form.tax_simplified_rate == null)) {
      const nextSimplified = nextToo
        ? (rates.simplified_too_rate ?? rates.simplified_rate ?? 4)
        : (rates.simplified_ip_rate ?? rates.simplified_rate ?? 4)
      set('tax_simplified_rate', nextSimplified)
    }
  }

  // Даты продаж по умолчанию = начало проекта; клиент потом может сдвинуть вперёд.
  const setPlanningFrom = (value) => {
    set('planning_from', value)
    if (!value) return
    if (!form.sales_start) set('sales_start', value)
    if (!form.planned_volume) set('planned_volume', value)
  }

  return (
    <div className="setup">
      <SetupRow
        icon={
          <RowIcon>
            <path d="M4 10h16l-1.2 9H5.2L4 10z" />
            <path d="M8 10V7a4 4 0 0 1 8 0v3" />
          </RowIcon>
        }
        title="Основная информация"
        text="Базовые данные о проекте"
      >
        <Field label="Название проекта" required>
          <TextInput {...f('name')} required maxLength={500} placeholder="Например, Кофейня «Бариста»" />
        </Field>
        <div className="setup-row__grid">
          <Field label="Форма бизнеса" required group>
            <Dropdown value={form.legal_form} options={LEGAL_FORMS} required placeholder="Выберите" onChange={setLegal} />
          </Field>
          <Field label="Плательщик НДС" required group>
            <Choice {...f('nds')} options={YES_NO} pair required />
          </Field>
        </div>
      </SetupRow>

      <SetupRow
        icon={
          <RowIcon>
            <rect x="5" y="3" width="14" height="18" rx="2" />
            <path d="M8 7h8M8 12h.01M12 12h.01M16 12h.01M8 16h.01M12 16h.01M16 16h.01" />
          </RowIcon>
        }
        title="Финансовые настройки"
        text="Параметры расчёта модели"
      >
        <div className="setup-row__grid">
          <Field label="Налоговый режим" required group>
            <Dropdown value={form.tax_regime} options={TAX_CHOICES} required placeholder="Выберите" onChange={setTaxRegime} />
          </Field>
          <Field label="Валюта" required group>
            <Dropdown {...f('currency')} options={CURRENCY_CHOICES} required placeholder="Выберите" />
          </Field>
          <Field label="Периодичность расчёта" required group>
            <Dropdown {...f('time_param')} options={PERIOD_CHOICES} required placeholder="Выберите" />
          </Field>
          <Field label="График работы, часов" required group>
            <Dropdown {...f('schedule')} options={SCHEDULE_CHOICES} required placeholder="Выберите" />
          </Field>
        </div>
      </SetupRow>

      <SetupRow
        icon={
          <RowIcon>
            <path d="M4 19h16M7 16V8M12 16V5M17 16v-5" />
          </RowIcon>
        }
        title="Ставки налогов и взносов"
        text="По умолчанию из справочника (админ). Можно поменять для этой модели — как премию за риск или «НБ РК + N%»."
      >
        <div className="setup-row__grid">
          {regime === 4 ? (
            <Field
              label={too ? 'Упрощёнка (ТОО)' : 'Упрощёнка (ИП)'}
              required
              field="tax_simplified_rate"
              hint={`Дефолт ${simplifiedDefault}% для ${too ? 'ТОО' : 'ИП'}. Маслихат может задать иной коридор.`}
            >
              <NumberInput
                required
                max={100}
                value={form.tax_simplified_rate}
                suffix="%"
                placeholder={String(simplifiedDefault)}
                onChange={(v) => set('tax_simplified_rate', v)}
              />
            </Field>
          ) : null}
          {regime === 1 ? (
            <Field
              label={too ? 'КПН' : 'ИПН / КПН режима'}
              required
              field="tax_cit_rate"
              hint={`Дефолт ${too ? (rates.cit_too_rate ?? 20) : (rates.cit_ip_rate ?? 10)}%`}
            >
              <NumberInput
                required
                max={100}
                value={form.tax_cit_rate}
                suffix="%"
                placeholder={String(too ? (rates.cit_too_rate ?? 20) : (rates.cit_ip_rate ?? 10))}
                onChange={(v) => set('tax_cit_rate', v)}
              />
            </Field>
          ) : null}
          {vatOn ? (
            <Field label="НДС" required field="vat_rate" hint={`Дефолт ${rates.vat_rate ?? 16}%. В ДДС при плательщике НДС.`}>
              <NumberInput
                required
                max={100}
                value={form.vat_rate}
                suffix="%"
                placeholder={String(rates.vat_rate ?? 16)}
                onChange={(v) => set('vat_rate', v)}
              />
            </Field>
          ) : null}
          {too && regime === 1 ? (
            <Field
              label="Социальный налог"
              required
              field="social_tax_rate"
              hint={`Дефолт ${rates.social_tax_rate ?? 6}% для ТОО на ОУР с 2026.`}
            >
              <NumberInput
                required
                max={100}
                value={form.social_tax_rate}
                suffix="%"
                placeholder={String(rates.social_tax_rate ?? 6)}
                onChange={(v) => set('social_tax_rate', v)}
              />
            </Field>
          ) : null}
        </div>
      </SetupRow>

      <SetupRow
        icon={
          <RowIcon>
            <rect x="4" y="5" width="16" height="15" rx="2" />
            <path d="M8 3v4M16 3v4M4 10h16" />
          </RowIcon>
        }
        title="Период планирования"
        text="Горизонт расчёта и срок проекта"
      >
        <div className="setup-row__period">
          <Field label="Начало проекта" required group>
            <DateField required value={form.planning_from} onChange={setPlanningFrom} max={form.planning_to || undefined} />
          </Field>
          <Field label="Окончание проекта" required group>
            <DateField required value={form.planning_to} onChange={(v) => set('planning_to', v)} min={form.planning_from || undefined} />
          </Field>
          <div className={`setup-horizon ${form.planning_from && form.planning_to && !months ? 'setup-horizon--bad' : ''}`}>
            <span className="setup-horizon__icon">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round">
                <circle cx="12" cy="12" r="8" />
                <path d="M12 8v5l3 2" />
              </svg>
            </span>
            <span>
              <span>Горизонт планирования</span>
              <strong>
                {months
                  ? `${months} ${plural(months, 'месяц', 'месяца', 'месяцев')}`
                  : form.planning_from && form.planning_to
                    ? 'Проверьте даты'
                    : 'Не выбран'}
              </strong>
            </span>
          </div>
        </div>
        {horizonExtension(form) ? <p className="setup-warning">{horizonExtension(form)}</p> : null}
      </SetupRow>
    </div>
  )
}

const PROGRAM_GROUPS = [
  ['loan', 'Кредитование'],
  ['subsidy', 'Субсидия ставки'],
  ['leasing', 'Лизинг'],
]

const GUARANTEE_KIND = 'guarantee'

function termCaption(months) {
  const count = Number(months)
  if (!count) return 'в каталоге не указан'
  if (count % 12) return `${count} мес.`
  const years = count / 12
  const tail = years % 100
  const word = tail >= 11 && tail <= 14 ? 'лет' : years % 10 === 1 ? 'год' : [2, 3, 4].includes(years % 10) ? 'года' : 'лет'
  return `${years} ${word} · ${count} мес.`
}

function percentText(value) {
  return `${new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 2 }).format(value)}%`
}

function CreditAmountNote({ form, sign }) {
  const summary = creditSummary(form)
  if (!summary.applicable) return null
  return (
    <div className="credit-sum-note">
      <p>
        <strong>Сумма кредита:</strong> {formatMoney(summary.total, sign)}
        {summary.total <= 0
          ? ' — появится, когда на шаге инвестиций будет потребность (CAPEX, ФОТ, аренда и т.д.).'
          : null}
      </p>
      {summary.total > 0 && (summary.working > 0 || summary.investment > 0) ? (
        <p className="fm-field__hint">
          {summary.working > 0
            ? `Инвестиционный займ ${formatMoney(summary.investment, sign)}, оборотный ${formatMoney(summary.working, sign)}.`
            : 'Считается из привлекаемых сумм на шаге инвестиций (оборудование, ФОТ, расходы). Редактировать сумму отдельно не нужно.'}
        </p>
      ) : null}
    </div>
  )
}

function AnnuityNote({ form, sign }) {
  const sketch = loanSketch(form)
  const summary = creditSummary(form)
  const monthRate = percentText(sketch.monthly)
  const yearRate = percentText(sketch.annual)
  const rateHint = sketch.subsidyInactive
    ? `сейчас субсидия не действует — клиент платит ставку банка ${percentText(sketch.bankAnnual)} (месяц ${monthRate})`
    : sketch.dual && sketch.subsidyTerm > 0
      ? `ставка клиента ${yearRate} (${monthRate} в месяц) до ${sketch.subsidyTerm} мес. субсидии, затем банк ${percentText(sketch.bankAnnual)}`
      : `годовая ставка ${yearRate}, в месяц ${monthRate}`
  if (!sketch.annuity) {
    return (
      <div className="annuity-note">
        <CreditAmountNote form={form} sign={sign} />
        {sketch.subsidyInactive ? (
          <p className="setup-warning">Субсидия не действует, клиент платит полную ставку банка.</p>
        ) : null}
        <p>
          Дифференцированный график. Проценты месяца — как в Excel «График платежей»: {rateHint}.
          После каникул тело гасится равными долями, поэтому взнос каждый месяц меньше.
        </p>
      </div>
    )
  }
  return (
    <div className="annuity-note">
      <CreditAmountNote form={form} sign={sign} />
      {sketch.subsidyInactive ? (
        <p className="setup-warning">Субсидия не действует, клиент платит полную ставку банка.</p>
      ) : null}
      <p>
        Аннуитет. Проценты месяца — как в Excel «График платежей»: {rateHint}.
        {sketch.grace ? ` Первые ${sketch.grace} мес. платятся только эти проценты, тело не гасится.` : ''}
        {sketch.paying ? ` Следующие ${sketch.paying} мес. взнос одинаковый: из него сначала проценты месяца, остальное уменьшает долг. Доля процентов во взносе к концу срока меньше.` : ''}
        {Number(form.wc_term) ? ' Заём на оборотные средства аннуитетом не идёт: там тело равными долями.' : ''}
      </p>
      {sketch.principal > 0 && sketch.marks.length ? (
        <table>
          <thead>
            <tr>
              <th>Месяц</th>
              <th>Взнос</th>
              <th>Проценты</th>
              <th>Доля взноса</th>
              <th>Тело</th>
              <th>Остаток</th>
            </tr>
          </thead>
          <tbody>
            {sketch.marks.map((row) => (
              <tr key={row.month}>
                <td>{row.grace ? `${row.month} · каникулы` : row.month}</td>
                <td>{formatMoney(row.due, sign)}</td>
                <td>{formatMoney(row.interest, sign)}</td>
                <td>{percentText(row.share * 100)}</td>
                <td>{formatMoney(row.body, sign)}</td>
                <td>{formatMoney(row.left, sign)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      ) : (
        <p>
          {summary.total > 0
            ? `Сумма кредита ${formatMoney(summary.total, sign)}. Процент месяца от тела займа.`
            : 'Сумма кредита появится, когда на шаге инвестиций будет потребность. Процент месяца от этого не меняется.'}
        </p>
      )}
    </div>
  )
}

function programCard(program) {
  if (!program) return null
  return (
    <div className="program-logic">
      <p>{program.logic || program.detail || program.kind_label || ''}</p>
      <dl>
        {program.kind === GUARANTEE_KIND ? (
          <>
            <div>
              <dt>Тип</dt>
              <dd>Гарантия Damu</dd>
            </div>
            <div>
              <dt>Сумма / покрытие</dt>
              <dd>{program.amount || program.detail || 'в каталоге нет'}</dd>
            </div>
            <div>
              <dt>Назначение</dt>
              <dd>{program.purpose || '—'}</dd>
            </div>
          </>
        ) : (
          <>
            <div>
              <dt>Минимальный срок</dt>
              <dd>{termCaption(program.term_min_months)}</dd>
            </div>
            <div>
              <dt>Максимальный срок</dt>
              <dd>{termCaption(program.term_max_months)}</dd>
            </div>
            <div>
              <dt>Ставка для клиента</dt>
              <dd>
                {program.rate != null || program.client_rate != null
                  ? `${program.rate ?? program.client_rate}%`
                  : 'в каталоге нет — укажите ниже'}
              </dd>
            </div>
            <div>
              <dt>Сумма</dt>
              <dd>{program.amount || 'в каталоге нет'}</dd>
            </div>
            <div>
              <dt>Каникулы</dt>
              <dd>{program.grace_max_months ? `до ${program.grace_max_months} мес.` : 'каталог не указывает, в модели до 36'}</dd>
            </div>
            <div>
              <dt>Оборотные средства</dt>
              <dd>{program.allows_wc === false ? 'отсутствует' : 'можно'}</dd>
            </div>
          </>
        )}
      </dl>
    </div>
  )
}

function GovProgramField({ form, set, notify }) {
  const [programs, setPrograms] = useState([])
  const [note, setNote] = useState('')
  const chosen = form.gov_program && typeof form.gov_program === 'object' ? form.gov_program : null
  const guarantee = form.gov_guarantee && typeof form.gov_guarantee === 'object' ? form.gov_guarantee : null
  const ratePrograms = programs.filter((item) => item.kind !== GUARANTEE_KIND)
  const guaranteePrograms = programs.filter((item) => item.kind === GUARANTEE_KIND)

  useEffect(() => {
    let live = true
    api.govPrograms()
      .then((data) => {
        if (!live) return
        setPrograms(data.programs || [])
        if (!data.programs?.length) setNote('Список Damu сейчас пуст. Ставку и срок можно указать вручную.')
        else if (data.stale) setNote('Показан прошлый список Damu: сайт сейчас не ответил.')
      })
      .catch(() => {
        if (live) setNote('Список Damu не открылся. Ставку и срок можно указать вручную.')
      })
    return () => {
      live = false
    }
  }, [])

  // Старые черновики: гарантия в основном поле или гарантия без программы — убираем «голую» гарантию.
  useEffect(() => {
    if (chosen?.kind === GUARANTEE_KIND) {
      set('gov_program', null)
      set('gov_guarantee', null)
      return
    }
    if (!chosen?.slug && guarantee?.slug) set('gov_guarantee', null)
  }, [chosen, guarantee, set])

  const pick = (slug) => {
    if (!slug) {
      set('gov_program', null)
      return
    }
    const program = programs.find((item) => item.slug === slug)
    if (!program) return
    // Старые черновики могли держать гарантию в основном поле — перенесём в дополнение.
    if (program.kind === GUARANTEE_KIND) {
      set('gov_program', null)
      set('gov_guarantee', program)
      notify?.('ok', `${programTitle(program.title)} добавлена как гарантия Damu`)
      return
    }
    if (program.allows_investment === false) {
      set('gov_program', form.gov_program || null)
      notify?.('error', `${programTitle(program.title)} не покрывает помещение и оборудование. Для этого кредита работайте без госпрограммы.`)
      return
    }
    const wcFilled = num(form.wc_term) > 0 || num(form.wc_grace) > 0 || String(form.wc_rate ?? '').trim() !== ''
    const next = { ...form, gov_program: program }
    // Ставка для клиента из каталога (для Damu / Іскер аймақ — обычно 12,6%).
    const clientRate = program.rate ?? program.client_rate
    if (clientRate != null && clientRate !== '') next.loan_rate = clientRate
    // Срок субсидии = срок кредита (не больше максимума программы).
    const subsidyDefault = suggestedSubsidyTerm(next, program)
    if (subsidyDefault !== '') next.loan_subsidy_term = subsidyDefault
    if (program.term_kind === 'subsidy' && !form.loan_annuity) next.loan_annuity = true
    if (program.allows_wc === false) {
      next.wc_term = ''
      next.wc_grace = ''
      next.wc_rate = ''
    }
    const aligned = alignProgram(next)
    const patch = { ...(aligned?.patch || {}) }
    if (clientRate != null && clientRate !== '') patch.loan_rate = clientRate
    const afterAlign = { ...next, ...patch }
    const subsidy = suggestedSubsidyTerm(afterAlign, program)
    if (subsidy !== '') patch.loan_subsidy_term = subsidy
    if (program.term_kind === 'subsidy' && !form.loan_annuity) patch.loan_annuity = true
    const graceMax = Number(program.grace_max_months) || 0
    if (graceMax && Number(form.loan_grace) > graceMax) patch.loan_grace = graceMax
    // Субсидия Damu = банк − клиент (при выбранной ставке клиента из программы).
    const synced = syncGovLoanRates({ ...form, gov_program: program, ...patch }, patch, 'client')
    if (asText(synced.loan_bank_rate) !== '' && asText(synced.loan_rate) !== '') {
      patch.loan_subsidy_rate = synced.loan_subsidy_rate
      patch.loan_rate = synced.loan_rate
    }
    const bits = []
    if (patch.loan_rate != null && Math.abs(num(form.loan_rate) - Number(patch.loan_rate)) > 0.001) bits.push(`ставка клиента ${patch.loan_rate}%`)
    if (patch.loan_term != null && Number(form.loan_term) !== Number(patch.loan_term)) bits.push(`срок ${patch.loan_term} мес.`)
    if (patch.loan_grace != null) bits.push(`каникулы ${patch.loan_grace} мес.`)
    if (patch.loan_subsidy_term != null) bits.push(`срок субсидии ${patch.loan_subsidy_term} мес.`)
    if (patch.loan_annuity) bits.push('аннуитет после льготы')
    if (program.allows_wc === false) {
      patch.wc_term = ''
      patch.wc_grace = ''
      patch.wc_rate = ''
      if (wcFilled) bits.push('оборотные средства отсутствуют')
    }
    set('gov_program', program)
    Object.entries(patch).forEach(([key, value]) => set(key, value))
    if (bits.length) notify?.('ok', `По программе ${programTitle(program.title)}: ${bits.join(', ')}`)
  }

  const pickGuarantee = (slug) => {
    if (!slug) {
      set('gov_guarantee', null)
      return
    }
    if (!chosen?.slug || chosen.kind === GUARANTEE_KIND) {
      notify?.('error', 'Сначала выберите госпрограмму (субсидия / кредит / лизинг). Гарантия — только как дополнение.')
      return
    }
    const program = guaranteePrograms.find((item) => item.slug === slug) || programs.find((item) => item.slug === slug)
    if (!program || program.kind !== GUARANTEE_KIND) return
    set('gov_guarantee', program)
    notify?.('ok', `Гарантия ${programTitle(program.title)} добавлена к ${programTitle(chosen.title)}. Ставку не меняет.`)
  }

  const knownMain = !chosen || ratePrograms.some((item) => item.slug === chosen.slug) || programs.some((item) => item.slug === chosen.slug)
  const knownGuarantee = !guarantee || guaranteePrograms.some((item) => item.slug === guarantee.slug)
  const canAddGuarantee = Boolean(chosen?.slug && chosen.kind !== GUARANTEE_KIND)

  return (
    <div className="sales-loans__program">
      <Field
        label="Госпрограмма"
        hint={note || 'Субсидия, льготный кредит или лизинг. Ставка и сроки кредита берутся отсюда.'}
        wide
      >
        <select
          value={chosen?.slug || ''}
          onChange={(event) => {
            const slug = event.target.value
            if (!slug) set('gov_guarantee', null)
            pick(slug)
          }}
        >
          <option value="">Без госпрограммы</option>
          {!knownMain && chosen ? <option value={chosen.slug}>{chosen.title}</option> : null}
          {PROGRAM_GROUPS.map(([kind, label]) => {
            const items = ratePrograms.filter((item) => item.kind === kind)
            if (!items.length) return null
            return (
              <optgroup key={kind} label={label}>
                {items.map((item) => (
                  <option key={item.slug} value={item.slug}>
                    {item.title}
                    {item.rate != null || item.client_rate != null
                      ? ` · клиент ${item.rate ?? item.client_rate}%`
                      : ''}
                  </option>
                ))}
              </optgroup>
            )
          })}
        </select>
      </Field>
      {programCard(chosen)}

      <Field
        label="Гарантия Damu"
        hint={
          canAddGuarantee
            ? 'Дополнение к выбранной госпрограмме. На ставку клиента не влияет — только покрытие гарантийного фонда.'
            : 'Сначала выберите госпрограмму выше. Одну гарантию без программы взять нельзя.'
        }
        wide
      >
        <select
          value={canAddGuarantee ? guarantee?.slug || '' : ''}
          onChange={(event) => pickGuarantee(event.target.value)}
          disabled={!canAddGuarantee || (!guaranteePrograms.length && knownGuarantee)}
        >
          <option value="">{canAddGuarantee ? 'Без гарантии' : 'Сначала выберите госпрограмму'}</option>
          {canAddGuarantee && !knownGuarantee && guarantee ? (
            <option value={guarantee.slug}>{guarantee.title}</option>
          ) : null}
          {canAddGuarantee
            ? guaranteePrograms.map((item) => (
                <option key={item.slug} value={item.slug}>
                  {item.title}
                  {item.amount ? ` · ${item.amount}` : ''}
                </option>
              ))
            : null}
        </select>
      </Field>
      {canAddGuarantee ? programCard(guarantee) : null}
    </div>
  )
}

export function SalesStep({ form, set, notify }) {
  const sign = CURRENCY_SIGNS[form.currency]
  const bounds = planningBounds(form)
  // Загрузка по годам — только период с шага 1 (planning_to), без продления под инвестора/кредит.
  const planTo = bounds.asked || form.planning_to
  const years = yearsBetween(bounds.from || form.planning_from, planTo)
  const highlight = useHighlightField()
  const fullStart = fullLoadAtStart(form)
  const salesProblem = form.sales_start
    ? dateRangeIssue(form.sales_start, bounds.from, planTo, 'Начало продаж')
    : highlight === 'sales_start'
      ? dateRangeIssue(form.sales_start, bounds.from, planTo, 'Начало продаж')
      : ''
  const volumeProblem = (() => {
    if (!form.planned_volume && highlight !== 'planned_volume') return ''
    const range = dateRangeIssue(form.planned_volume, bounds.from, planTo, 'Выход на 100%')
    if (range) return range
    if (
      form.sales_start &&
      form.planned_volume &&
      String(form.planned_volume).slice(0, 7) < String(form.sales_start).slice(0, 7)
    ) {
      return '«Выход на 100%» не может быть раньше начала продаж'
    }
    return ''
  })()
  const salesHint =
    salesProblem ||
    (form.planning_from
      ? 'По умолчанию — начало проекта. Раньше этой даты выбрать нельзя'
      : 'Сначала укажите начало проекта на шаге 1')
  const volumeHint =
    volumeProblem ||
    (form.planning_from
      ? 'По умолчанию — начало проекта. Не раньше старта продаж'
      : 'Дата, когда продажи выходят на плановый объём')
  const setCapacity = (year, value) => set('planned_capacity', { ...form.planned_capacity, [year]: value })
  const [assetTab, setAssetTab] = useState('equipment')
  const [nbk, setNbk] = useState(null)

  useEffect(() => {
    const onTab = (event) => {
      const tab = event?.detail
      if (tab && ASSET_TABS.some(([key]) => key === tab)) setAssetTab(tab)
    }
    window.addEventListener('fm-asset-tab', onTab)
    return () => window.removeEventListener('fm-asset-tab', onTab)
  }, [])
  const mode = Number(form.financing)
  const program = form.gov_program && typeof form.gov_program === 'object' ? form.gov_program : null
  const termHint = program?.term_kind === 'loan'
    ? `По программе: минимум ${termCaption(program.term_min_months)}, максимум ${termCaption(program.term_max_months)}`
    : program?.term_kind === 'subsidy'
      ? 'Полный срок кредита. Срок субсидии — в карточке программы выше.'
      : ''
  const graceHint = program?.grace_max_months
    ? `По программе не больше ${program.grace_max_months} мес., без тела долга`
    : 'До 36 месяцев, без тела долга'
  const wcBlocked = program?.allows_wc === false

  useEffect(() => {
    if (mode !== 2 && mode !== 4) return undefined
    let live = true
    api.nbkBaseRate()
      .then((data) => {
        if (live) setNbk(data)
      })
      .catch(() => {
        if (live) setNbk(null)
      })
    return () => {
      live = false
    }
  }, [mode])

  // Если на шаге 1 уже есть начало проекта, а даты продаж пустые — подставить их.
  useEffect(() => {
    if (!form.planning_from) return
    if (!form.sales_start) set('sales_start', form.planning_from)
    if (!form.planned_volume) set('planned_volume', form.planning_from)
  }, [form.planning_from, form.sales_start, form.planned_volume, set])

  const editLoan = (keyOrPatch, value) => {
    const changes = typeof keyOrPatch === 'object' && keyOrPatch != null ? keyOrPatch : { [keyOrPatch]: value }
    let next = { ...form, ...changes }
    const message = programBreak(next)
    if (message) {
      set('gov_program', null)
      set('gov_guarantee', null)
      set('loan_rate', '')
      set('wc_rate', '')
      set('loan_bank_rate', '')
      set('loan_subsidy_rate', '')
      set('loan_subsidy_term', '')
      set('loan_nbk_plus_4', false)
      Object.entries(changes).forEach(([itemKey, itemValue]) => {
        if (!['loan_rate', 'wc_rate', 'loan_bank_rate', 'loan_subsidy_rate', 'loan_subsidy_term', 'loan_nbk_plus_4'].includes(itemKey)) {
          set(itemKey, itemValue)
        }
      })
      notify?.('error', message)
      return
    }
    // При смене срока кредита и активной программе подтянуть срок субсидии, если он 0 или больше нового срока.
    const hasProgram = Boolean(next.gov_program && typeof next.gov_program === 'object' && next.gov_program.slug)
    if (Object.prototype.hasOwnProperty.call(changes, 'loan_term') && hasProgram) {
      const suggested = suggestedSubsidyTerm(next)
      if (suggested !== '' && (!num(next.loan_subsidy_term) || num(next.loan_subsidy_term) > num(suggested))) {
        changes.loan_subsidy_term = suggested
      }
    }
    Object.entries(changes).forEach(([itemKey, itemValue]) => set(itemKey, itemValue))
  }

  const applyNbkPlus4 = (on) => {
    if (!on) {
      editLoan({ loan_nbk_plus_4: false })
      return
    }
    const bank = nbk?.bank_plus_4
    if (bank == null) {
      const label = nbk?.label || 'НБ РК + 4%'
      notify?.('error', `Базовая ставка НБРК сейчас недоступна. Включите «${label}» чуть позже или укажите ставку банка вручную.`)
      return
    }
    const synced = syncGovLoanRates(
      { ...form, loan_nbk_plus_4: true, loan_bank_rate: bank },
      { loan_nbk_plus_4: true, loan_bank_rate: bank },
      asText(form.loan_rate) !== '' ? 'client' : 'subsidy',
    )
    editLoan({
      loan_nbk_plus_4: true,
      loan_bank_rate: synced.loan_bank_rate,
      loan_rate: synced.loan_rate,
      loan_subsidy_rate: synced.loan_subsidy_rate,
    })
    const spread = nbk.spread ?? 4
    notify?.('ok', `Ставка банка ${bank}%: базовая НБРК ${nbk.rate}% на ${nbk.as_of} + ${spread} п.п.`)
  }
  const projectStart = form.planning_from || ''
  const assetTotal = ASSET_TABS.reduce((sum, [key]) => sum + (form[key] || []).reduce((part, item) => part + num(item.price), 0), 0)

  return (
    <div className="setup">
      <SetupRow
        section="finance"
        icon={
          <RowIcon>
            <circle cx="12" cy="12" r="8" />
            <path d="M12 8v8M9.5 10.5c.6-.8 1.4-1.2 2.5-1.2 1.4 0 2.3.8 2.3 1.9 0 2.2-4.8 1.2-4.8 3.4 0 1 .9 1.8 2.4 1.8 1.1 0 2-.4 2.6-1.2" />
          </RowIcon>
        }
        title="Финансирование"
        text="Откуда придут привлекаемые деньги"
      >
        <div className="sales-modes">
          <Choice value={form.financing} onChange={(v) => set('financing', v)} options={FINANCING_CHOICES} />
        </div>
        {mode === 1 || mode === 4 ? (
          <div className="setup-row__grid">
            <Field label="Срок участия" required field="investor_term">
              <NumberInput integer required max={100} value={form.investor_term} suffix="лет" onChange={(v) => set('investor_term', v)} placeholder="лет" />
            </Field>
            <Field label="Дивиденды" required field="investor_dividend" hint="Доля прибыли прошлого года">
              <NumberInput required max={100} value={form.investor_dividend} suffix="%" onChange={(v) => set('investor_dividend', v)} placeholder="%" />
            </Field>
            <Field label="Премия за риск проекта" required field="risk_premium" hint="Сверх базовой ставки НБРК. Входит в цену денег инвестора и в WACC">
              <NumberInput required max={100} value={form.risk_premium} suffix="%" onChange={(v) => set('risk_premium', v)} placeholder="%" />
            </Field>
          </div>
        ) : null}
        {mode === 2 || mode === 4 ? (
          <div className="sales-loans">
            <GovProgramField form={form} set={set} notify={notify} />
            <div>
              <h4>На инвестиции</h4>
              <p>Помещение, оборудование, ремонт и строительство</p>
              <div className="setup-row__grid">
                <Field label="Срок" required field="loan_term" hint={termHint}>
                  <NumberInput
                    integer
                    required
                    max={250}
                    value={form.loan_term}
                    suffix="мес."
                    placeholder="мес."
                    onChange={(value) => editLoan('loan_term', value)}
                  />
                </Field>
                <Field label="Каникулы" field="loan_grace" hint={graceHint}>
                  <NumberInput integer max={36} value={form.loan_grace} suffix="мес." onChange={(value) => editLoan('loan_grace', value)} placeholder="0" />
                </Field>
                {!program ? (
                  <Field label="Ставка" required hint="Годовая ставка по кредиту">
                    <NumberInput
                      required
                      max={100}
                      value={form.loan_rate}
                      suffix="%"
                      placeholder="%"
                      onChange={(value) => editLoan('loan_rate', value)}
                    />
                  </Field>
                ) : null}
                <Field label="График" group>
                  <Checkbox value={form.loan_annuity} onChange={(v) => set('loan_annuity', v)} label="Аннуитет" />
                  <Toggle
                    value={Boolean(form.loan_nbk_plus_4)}
                    onChange={applyNbkPlus4}
                    label={nbk?.label || 'НБ РК + 4%'}
                  />
                </Field>
              </div>
              {nbk && form.loan_nbk_plus_4 ? (
                <p className="sales-note">
                  Банк {nbk.bank_plus_4}% = базовая НБРК {nbk.rate}% на {nbk.as_of} + {nbk.spread ?? 4} п.п.
                </p>
              ) : null}
              {program ? (
                <div className="sales-loans__tranche">
                  <h4>Условия транша (госпрограмма)</h4>
                  <p>
                    Как в банковском графике Damu: банк начисляет свою ставку, субсидия снижает платёж клиента на срок программы,
                    после окончания субсидии клиент платит полную ставку банка. В ОПиУ и ДДС — только то, что платит клиент.
                  </p>
                  <div className="setup-row__grid sales-loans__bank">
                    <Field
                      label="Ставка банка"
                      hint={
                        form.loan_nbk_plus_4
                          ? `Включено «${nbk?.label || 'НБ РК + 4%'}». Ручная правка снимет тумблер.`
                          : `Номинал до субсидии. Или тумблер «${nbk?.label || 'НБ РК + 4%'}».`
                      }
                    >
                      <NumberInput
                        max={100}
                        value={form.loan_bank_rate}
                        suffix="%"
                        placeholder="%"
                        onChange={(value) => {
                          const synced = syncGovLoanRates(
                            { ...form, loan_bank_rate: value, loan_nbk_plus_4: false },
                            { loan_bank_rate: value, loan_nbk_plus_4: false },
                            asText(form.loan_rate) !== '' ? 'client' : 'subsidy',
                          )
                          editLoan({
                            loan_bank_rate: synced.loan_bank_rate,
                            loan_nbk_plus_4: false,
                            loan_rate: synced.loan_rate,
                            loan_subsidy_rate: synced.loan_subsidy_rate,
                          })
                        }}
                      />
                    </Field>
                    <Field
                      label="Ставка для клиента"
                      hint={
                        program.rate != null || program.client_rate != null
                          ? `По программе ${program.rate ?? program.client_rate}% (можно поменять вручную)`
                          : 'То, что платит проект в срок субсидии'
                      }
                    >
                      <NumberInput
                        max={100}
                        value={form.loan_rate}
                        suffix="%"
                        placeholder="%"
                        onChange={(value) => {
                          const synced = syncGovLoanRates(
                            { ...form, loan_rate: value },
                            { loan_rate: value },
                            'client',
                          )
                          editLoan({
                            loan_rate: synced.loan_rate,
                            loan_subsidy_rate: synced.loan_subsidy_rate,
                          })
                        }}
                      />
                    </Field>
                    <Field label="Субсидия Damu" hint="Считается как банк − клиент">
                      <NumberInput
                        max={100}
                        value={form.loan_subsidy_rate}
                        suffix="%"
                        placeholder="%"
                        onChange={(value) => {
                          const synced = syncGovLoanRates(
                            { ...form, loan_subsidy_rate: value },
                            { loan_subsidy_rate: value },
                            'subsidy',
                          )
                          editLoan({
                            loan_subsidy_rate: synced.loan_subsidy_rate,
                            loan_rate: synced.loan_rate,
                          })
                        }}
                      />
                    </Field>
                    <Field
                      label="Срок субсидии"
                      hint={
                        program.term_kind === 'subsidy' && program.term_max_months
                          ? `По каталогу до ${program.term_max_months} мес. (${Math.round(program.term_max_months / 12)} г.)`
                          : 'По умолчанию = срок кредита. После окончания клиент платит ставку банка'
                      }
                    >
                      <NumberInput
                        integer
                        max={250}
                        value={form.loan_subsidy_term}
                        suffix="мес."
                        placeholder="мес."
                        onChange={(value) => editLoan('loan_subsidy_term', value)}
                      />
                    </Field>
                  </div>
                  {!num(form.loan_subsidy_term) && asText(form.loan_bank_rate) !== '' ? (
                    <p className="setup-warning">Субсидия не действует, клиент платит полную ставку банка.</p>
                  ) : null}
                </div>
              ) : null}
            </div>
            <div>
              <h4>На оборотные средства</h4>
              {wcBlocked ? <p>Отсутствует</p> : (
                <>
                  <p>Срок от 24 до 36 месяцев</p>
                  <div className="setup-row__grid">
                    <Field label="Срок">
                      <NumberInput integer min={24} max={36} value={form.wc_term} suffix="мес." onChange={(value) => editLoan('wc_term', value)} placeholder="24–36" />
                    </Field>
                    <Field label="Каникулы">
                      <NumberInput integer max={36} value={form.wc_grace} suffix="мес." onChange={(value) => editLoan('wc_grace', value)} placeholder="0" />
                    </Field>
                    <Field label="Ставка" hint={program?.term_kind === 'subsidy' && program.rate != null ? `По программе ${program.rate}%` : ''}>
                      <NumberInput
                        max={100}
                        value={form.wc_rate}
                        suffix="%"
                        placeholder="%"
                        onChange={(value) => editLoan('wc_rate', value)}
                      />
                    </Field>
                  </div>
                </>
              )}
            </div>
            <AnnuityNote form={form} sign={sign} />
          </div>
        ) : null}
        {mode === 3 && <p className="sales-note">Для гранта дополнительные условия здесь не нужны. Сумму укажете на шаге инвестиций.</p>}
      </SetupRow>

      <SetupRow
        section="dates"
        alert={Boolean(salesProblem || volumeProblem)}
        icon={
          <RowIcon>
            <rect x="4" y="5" width="16" height="15" rx="2" />
            <path d="M8 3v4M16 3v4M4 10h16" />
          </RowIcon>
        }
        title="Сроки продаж"
        text="Старт, загрузка в первый месяц и выход на 100%"
      >
        <div className="setup-row__grid">
          <Field
            label="Начало продаж"
            required
            field="sales_start"
            error={Boolean(salesProblem)}
            hint={salesHint}
          >
            <DateField
              required
              value={form.sales_start}
              min={form.planning_from || undefined}
              max={form.planning_to || undefined}
              onChange={(v) => {
                set('sales_start', v)
                // Если выход на 100% пустой или оказался раньше старта продаж — подтянуть.
                if (!form.planned_volume || String(form.planned_volume).slice(0, 10) < String(v).slice(0, 10)) {
                  set('planned_volume', v)
                }
              }}
            />
          </Field>
          <Field
            label="Загрузка в первый месяц"
            required={!fullStart}
            field="sales_start_load"
            hint={
              fullStart
                ? 'Выход на 100% в месяц старта — разгона нет, с первого месяца 100%. Для разгона поставьте «Выход на 100%» позже'
                : 'От этой доли линейный рост до 100% к дате выхода на объём'
            }
          >
            <NumberInput
              required={!fullStart}
              disabled={fullStart}
              max={100}
              value={fullStart ? 100 : form.sales_start_load}
              suffix="%"
              placeholder="50"
              onChange={(v) => set('sales_start_load', v)}
            />
          </Field>
          <Field
            label="Выход на 100%"
            required
            field="planned_volume"
            error={Boolean(volumeProblem)}
            hint={volumeHint}
          >
            <DateField
              required
              value={form.planned_volume}
              min={form.sales_start || form.planning_from || undefined}
              max={form.planning_to || undefined}
              onChange={(v) => set('planned_volume', v)}
            />
          </Field>
        </div>
        {form.planning_from && form.sales_start && String(form.sales_start).slice(0, 7) <= String(form.planning_from).slice(0, 7) ? (
          <p className="setup-warning">
            Ремонт, закупки и найм до открытия: поставьте начало проекта раньше начала продаж. Выручки в эти месяцы не будет; в расходах включите «С начала проекта», чтобы платить и в месяцы подготовки.
          </p>
        ) : null}
      </SetupRow>

      <SetupRow
        section="products"
        icon={
          <RowIcon>
            <path d="M6 7h12l-1 12H7L6 7z" />
            <path d="M9 7V5h6v2" />
          </RowIcon>
        }
        title="Продукция"
        text="Количество и цена при полной загрузке"
      >
        <ProductList
          items={form.volume_sales}
          onChange={(rows) => set('volume_sales', rows)}
          sign={sign}
          monthly={Number(form.time_param) !== 1}
          years={years}
          dateMin={form.planning_from || undefined}
          dateMax={form.planning_to || undefined}
        />
      </SetupRow>

      <SetupRow
        section="capacity"
        icon={
          <RowIcon>
            <path d="M4 19V9M10 19V5M16 19v-7M22 19H2" />
          </RowIcon>
        }
        title="Загрузка по годам"
        text="Только годы периода планирования с шага 1. Пустые годы растут по месяцам от начала продаж до выхода на 100%."
      >
        {years.length === 0 ? (
          <p className="sales-note">Сначала укажите период планирования на шаге 1.</p>
        ) : (
          <div className="sales-years">
            {years.map((year) => (
              <Field key={year} label={String(year)}>
                <NumberInput
                  max={1000}
                  suffix="%"
                  value={form.planned_capacity[year] ?? ''}
                  onChange={(v) => setCapacity(year, v)}
                  placeholder="авто"
                />
              </Field>
            ))}
          </div>
        )}
        {bounds.extended ? (
          <p className="sales-note">
            Из‑за срока инвестора/кредита Excel продлит расчёт до {String(bounds.to).slice(5, 7)}.{String(bounds.to).slice(0, 4)},
            но загрузку здесь задаёте только до конца проекта ({years[0]}–{years[years.length - 1]}). Дальше — «авто».
          </p>
        ) : null}
        {(years.some((year) => num(form.planned_capacity[year]) > 100) ||
          (form.volume_sales || []).some(
            (item) => item.own_capacity && years.some((year) => num(item.planned_capacity?.[year]) > 100),
          )) && (
          <p className="setup-warning">Загрузка выше 100%. Так можно только если мощность расширена: новые сотрудники, оборудование или CAPEX. Иначе модель умножит продажи на этот процент без дополнительных вложений.</p>
        )}
        {(years.some((year) => form.planned_capacity[year] !== '' && form.planned_capacity[year] !== undefined && num(form.planned_capacity[year]) < 0) ||
          (form.volume_sales || []).some(
            (item) =>
              item.own_capacity &&
              years.some((year) => item.planned_capacity?.[year] !== '' && item.planned_capacity?.[year] !== undefined && num(item.planned_capacity[year]) < 0),
          )) && <p className="sales-note">Отрицательная загрузка в расчёт не берётся.</p>}
      </SetupRow>

      <SetupRow
        section="income"
        icon={
          <RowIcon>
            <path d="M12 5v14M5 12h14" />
          </RowIcon>
        }
        title="Прочие доходы"
        text="Ежемесячные и ежегодные индексируются. Разовый доход записывается один раз и без индекса."
      >
        <div className="setup-row__grid">
          <Field label={Number(form.other_income_period) === 2 ? 'Сумма в год' : Number(form.other_income_period) === 3 ? 'Разовая сумма' : 'Сумма в месяц'}>
            <NumberInput value={form.other_income} suffix={sign} onChange={(v) => set('other_income', v)} placeholder="0" />
          </Field>
          <Field label="Начало" hint="пусто — с начала продаж">
            <DateField value={form.other_income_start || ''} onChange={(v) => set('other_income_start', v)} />
          </Field>
          <Field label="Как поступают">
            <Dropdown
              value={form.other_income_period || 1}
              options={OTHER_INCOME_PERIODS}
              onChange={(v) => set('other_income_period', Number(v))}
            />
          </Field>
        </div>
      </SetupRow>

      <SetupRow
        section="assets"
        icon={
          <RowIcon>
            <path d="M3 20V9l9-5 9 5v11" />
            <path d="M9 20v-6h6v6" />
          </RowIcon>
        }
        title="Активы"
        text={
          assetTotal
            ? `Всего ${formatMoney(assetTotal, sign)}. Срок амортизации — у каждой позиции (помещения обычно 25 лет).`
            : 'Оборудование, помещение, работы, материалы и товарный запас. Срок амортизации задаётся по каждой строке.'
        }
      >
        <div className="sales-tabs" role="tablist">
          {ASSET_TABS.map(([key, label]) => (
            <button
              key={key}
              type="button"
              role="tab"
              aria-selected={assetTab === key}
              className={`sales-tab ${assetTab === key ? 'sales-tab--on' : ''}`}
              onClick={() => setAssetTab(key)}
            >
              {label}
              <span>{(form[key] || []).length}</span>
            </button>
          ))}
        </div>
        {assetTab === 'inventory' ? (
          <InventoryCards
            items={form.inventory || []}
            onChange={(rows) => set('inventory', rows)}
            sign={sign}
          />
        ) : (
          <AssetCards
            category={assetTab}
            items={form[assetTab] || []}
            onChange={(rows) => set(assetTab, rows)}
            sign={sign}
            projectStart={projectStart}
          />
        )}
      </SetupRow>
    </div>
  )
}

export function ExpensesStep({ form, set, rateDefaults }) {
  const sign = CURRENCY_SIGNS[form.currency]
  const rates = rateDefaults || {}
  const [expenseTab, setExpenseTab] = useState('admin_expenses')
  const productOptions = form.volume_sales
    .map((row) => String(row.name || '').trim())
    .filter(Boolean)
    .map((name) => [name, name])
  const monthlyTotal = (form.salary || []).reduce((sum, item) => sum + num(item.count) * num(item.salary), 0)
  const cogs = zeroCogsState(form)
  const namedStaff = (form.salary || []).filter((item) => String(item.name || '').trim())
  const noProductionStaff =
    namedStaff.length > 0 &&
    namedStaff.every((item) => Number(item.industrial) === 0 && item.industrial !== '' && item.industrial != null) &&
    (form.volume_sales || []).some((row) => String(row.name || '').trim() && num(row.count) > 0 && num(row.price) > 0)

  return (
    <div className="setup">
      <SetupRow
        section="cogs"
        icon={
          <RowIcon>
            <path d="M4 7h16M7 7v12h10V7" />
            <path d="M9 11h6M9 15h4" />
          </RowIcon>
        }
        title="Себестоимость"
        text="Закупочная цена умножается на продажи. Платежи по графику идут отдельно и складываются с ней. Первоначальный запас из инвестиций сюда не копируется."
      >
        {productOptions.length === 0 ? (
          <p className="sales-note">Сначала укажите продукцию на шаге 2, если нужна цена на единицу.</p>
        ) : (
          <CostCards items={form.variable_costs} onChange={(rows) => set('variable_costs', rows)} sign={sign} products={productOptions} />
        )}
        <p className="sales-note">По графику: «каждые 2 месяца» — платёж через один, а не сумма за первые два месяца.</p>
        <ExpenseCards id="cogs" items={form.variable_other} onChange={(rows) => set('variable_other', rows)} sign={sign} />
        {cogs.zero ? (
          <div className="setup-warning zero-cogs">
            <p>
              Себестоимость равна нулю, а продажи есть: вся выручка станет валовой прибылью. Для торговли и производства
              это почти всегда ошибка — добавьте закупку, сырьё, упаковку и другие затраты на единицу продукции.
            </p>
            <Checkbox
              value={Boolean(form.zero_cogs_confirmed)}
              onChange={(v) => set('zero_cogs_confirmed', v)}
              label="Подтверждаю: переменной себестоимости у продаж нет (например, услуги без закупок)"
            />
          </div>
        ) : cogs.withoutCost.length ? (
          <p className="sales-note">Без себестоимости на единицу: {cogs.withoutCost.join(', ')}. Проверьте, что это так.</p>
        ) : null}
      </SetupRow>

      <SetupRow
        section="salary"
        icon={
          <RowIcon>
            <circle cx="12" cy="8" r="3" />
            <path d="M5 19c1.2-3 3.4-4.5 7-4.5S17.8 16 19 19" />
          </RowIcon>
        }
        title="Зарплаты"
        text={monthlyTotal ? `Оклад ${formatMoney(monthlyTotal, sign)} в месяц` : 'Должности, оклад и доля от выручки'}
      >
        <StaffCards
          items={form.salary}
          onChange={(rows) => set('salary', rows)}
          sign={sign}
          form={form}
          set={set}
          rates={rates}
        />
        {noProductionStaff ? (
          <p className="setup-warning">
            Все сотрудники отмечены как административные, поэтому в себестоимости нет зарплаты. Если кто-то делает продукт
            или оказывает услугу клиенту, выберите для него «Производственный».
          </p>
        ) : null}
      </SetupRow>

      <SetupRow
        section="fixed"
        icon={
          <RowIcon>
            <rect x="4" y="5" width="16" height="14" rx="2" />
            <path d="M8 9h8M8 13h5" />
          </RowIcon>
        }
        title="Постоянные расходы"
        text="Административные, маркетинг и земельный налог"
      >
        <div className="sales-tabs" role="tablist">
          {EXPENSE_TABS.map(([key, label]) => {
            const adminAll = form.admin_expenses || []
            const count =
              key === 'land_tax'
                ? adminAll.filter((row) => isLandTaxRow(row)).length
                : key === 'admin_expenses'
                  ? adminAll.filter((row) => !isLandTaxRow(row)).length
                  : (form[key] || []).length
            return (
              <button
                key={key}
                type="button"
                role="tab"
                aria-selected={expenseTab === key}
                className={`sales-tab ${expenseTab === key ? 'sales-tab--on' : ''}`}
                onClick={() => setExpenseTab(key)}
              >
                {label}
                <span>{count}</span>
              </button>
            )
          })}
        </div>
        {expenseTab === 'land_tax' ? (
          <ExpenseCards
            id="land-tax"
            landMode
            items={(form.admin_expenses || []).filter((row) => isLandTaxRow(row))}
            onChange={(rows) => {
              const rest = (form.admin_expenses || []).filter((row) => !isLandTaxRow(row))
              set(
                'admin_expenses',
                [
                  ...rest,
                  ...rows.map((row) => ({
                    ...row,
                    land_tax: true,
                    land_unit: row.land_unit === 'ha' ? 'ha' : 'm2',
                    period: row.period || 12,
                    type_expense: 0,
                    invest: false,
                  })),
                ],
              )
            }}
            sign={sign}
          />
        ) : (
          <ExpenseCards
            id={expenseTab === 'admin_expenses' ? 'fixed-admin' : 'fixed-marketing'}
            items={
              expenseTab === 'admin_expenses'
                ? (form.admin_expenses || []).filter((row) => !isLandTaxRow(row))
                : form.marketing_expenses || []
            }
            onChange={(rows) => {
              if (expenseTab === 'admin_expenses') {
                const land = (form.admin_expenses || []).filter((row) => isLandTaxRow(row))
                set('admin_expenses', [...rows, ...land])
                return
              }
              set('marketing_expenses', rows)
            }}
            sign={sign}
            withType={expenseTab === 'admin_expenses'}
          />
        )}
      </SetupRow>
    </div>
  )
}

function amountsMatch(left, right) {
  return INVEST_FIELDS.every(([key]) => num(left?.[key]) === num(right?.[key]))
}

export function InvestmentStep({ form, set }) {
  const sign = CURRENCY_SIGNS[form.currency]
  const rows = panelRows(form)
  const withOwn = Number(form.own_invest) === 1
  const mixed = Number(form.financing) === 4
  const totalsKey = rows.map((row) => `${row.key}:${Math.round(row.total)}`).join('|')
  const [detailKey, setDetailKey] = useState('')

  useEffect(() => {
    const current = panelRows(form)
    const own = {}
    const attracted = {}
    for (const [key] of INVEST_FIELDS) {
      const row = current.find((item) => item.key === key)
      const total = Math.round(row ? row.total : 0)
      // Не даём «своим» превышать потребность (например после already_owned).
      let ownValue = withOwn ? Math.round(num(form.own[key])) : 0
      if (ownValue > total) ownValue = total
      own[key] = ownValue
      attracted[key] = Math.max(0, total - ownValue)
    }
    if (!amountsMatch(form.own, own)) set('own', own)
    if (!amountsMatch(form.attracted, attracted)) set('attracted', attracted)
  }, [totalsKey, withOwn, form, set])

  useEffect(() => {
    if (!detailKey) return undefined
    const onKey = (event) => {
      if (event.key === 'Escape') setDetailKey('')
    }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [detailKey])

  const setMonths = (key, value) => set(`months_${key}`, value)
  const setOwn = (key, value) => set('own', { ...form.own, [key]: value })
  const setCredit = (key, value) => set('credit', { ...form.credit, [key]: value })
  const creditOf = (key) => (mixed ? num(form.credit?.[key]) : 0)
  const investmentTotal = rows.reduce((sum, row) => sum + row.total, 0)
  const ownTotal = withOwn ? rows.reduce((sum, row) => sum + num(form.own[row.key]), 0) : 0
  const creditTotal = rows.reduce((sum, row) => sum + creditOf(row.key), 0)
  const investorTotal = rows.reduce((sum, row) => {
    const own = withOwn ? num(form.own[row.key]) : 0
    return sum + Math.max(0, row.total - own - creditOf(row.key))
  }, 0)
  const attractedTotal = rows.reduce((sum, row) => sum + Math.max(0, row.total - (withOwn ? num(form.own[row.key]) : 0)), 0)
  const gap = fullLoadMonth(form)
  const debt = creditSummary(form)
  const detailRow = rows.find((row) => row.key === detailKey) || null
  const detailLines = detailRow ? investDetailLines(form, detailRow.key) : []
  const detailOwn = detailRow && withOwn ? num(form.own[detailRow.key]) : 0
  const detailCredit = detailRow ? creditOf(detailRow.key) : 0
  const detailAttracted = detailRow ? Math.max(0, detailRow.total - detailOwn) : 0
  const detailInvestor = Math.max(0, detailAttracted - detailCredit)
  const attractedLabel =
    Number(form.financing) === 2 ? 'Кредит' : Number(form.financing) === 3 ? 'Грант' : 'Привлекаемые'

  return (
    <div className="setup">
      {gap != null && gap < 0 ? (
        <p className="setup-warning">
          Проект убыточен даже на полной мощности: {formatMoney(gap, sign)} в месяц. Это видно до сборки фин модели.
        </p>
      ) : null}
      {debt.applicable ? (
        <p className="sales-note">
          <strong>Сумма кредита:</strong> {formatMoney(debt.total, sign)}
          {debt.working > 0
            ? ` (инвестиции ${formatMoney(debt.investment, sign)}, оборотные ${formatMoney(debt.working, sign)})`
            : Number(form.financing) === 2
              ? ' — из колонки «Кредит» ниже'
              : ''}
        </p>
      ) : null}
      <SetupRow
        icon={
          <RowIcon>
            <rect x="3" y="6" width="18" height="13" rx="2" />
            <path d="M3 10h18" />
          </RowIcon>
        }
        title="Свои вложения"
        text="Если да, укажите только свою часть. Остальное посчитается само."
      >
        <Field label="Участвуете своими деньгами" required>
          <Dropdown required value={form.own_invest} onChange={(v) => set('own_invest', v)} options={YES_NO} />
        </Field>
      </SetupRow>

      <SetupRow
        icon={
          <RowIcon>
            <path d="M4 7h16M7 7V5h10v2" />
            <path d="M6 7l1 13h10l1-13" />
          </RowIcon>
        }
        className="setup-row--sheet"
        title="На что нужны инвестиции"
        text={
          rows.length
            ? mixed
              ? `Капитальные затраты со шага 2. Для ежемесячных расходов укажите, на сколько месяцев их заложить. В колонке «Кредит» укажите заёмную часть, остаток привлекаемых — деньги инвестора. Всего ${formatMoney(investmentTotal, sign)}.`
              : `Капитальные затраты со шага 2. Для ежемесячных расходов укажите, на сколько месяцев их заложить. Всего ${formatMoney(investmentTotal, sign)}.`
            : 'Капитальные затраты со шага 2. Для ежемесячных расходов укажите, на сколько месяцев их заложить.'
        }
      >
        {rows.length === 0 ? (
          <p className="sales-note">На предыдущих шагах пока нет сумм.</p>
        ) : (
          <div className="invest-sheet">
            <table className="fm-panel">
              <thead>
                <tr>
                  <th>Вид затрат</th>
                  <th>Откуда сумма</th>
                  <th>В инвестиции</th>
                  {withOwn && <th>Свои</th>}
                  {mixed ? (
                    <>
                      <th>Инвестор</th>
                      <th>Кредит</th>
                    </>
                  ) : Number(form.financing) === 2 ? (
                    <th>Кредит</th>
                  ) : Number(form.financing) === 3 ? (
                    <th>Грант</th>
                  ) : (
                    <th>Привлекаемые</th>
                  )}
                  <th />
                </tr>
              </thead>
              <tbody>
                {rows.map((row) => {
                  const own = withOwn ? num(form.own[row.key]) : 0
                  const credit = creditOf(row.key)
                  const attracted = Math.max(0, row.total - own)
                  const investor = Math.max(0, attracted - credit)
                  const over = (withOwn && Math.round(own) > Math.round(row.total)) || (mixed && Math.round(credit) > Math.round(attracted))
                  return (
                    <tr key={row.key} className={over ? 'fm-panel--bad' : ''}>
                      <td>
                        {row.label}
                        {row.key === 'raw' && row.total > 0 ? (
                          <span className="invest-sheet__source">
                            Закупка запаса, не денежный резерв: оплачивается в первом месяце, остаток идёт в счёт
                            следующих поставок — второй раз не покупается
                          </span>
                        ) : null}
                      </td>
                      <td>
                        {row.operating ? (
                          row.stockListed ? (
                            <span className="invest-sheet__source">
                              Из списка «Товарный запас» · оборотный капитал, без амортизации
                            </span>
                          ) : row.periodAware ? (
                            <span className="fm-panel__calc">
                              На
                              <NumberInput
                                integer
                                max={120}
                                value={form[`months_${row.key}`]}
                                suffix="мес."
                                onChange={(v) => setMonths(row.key, v)}
                              />
                              запуска · с учётом периода (год ≠ × месяцев)
                            </span>
                          ) : (
                            <span className="fm-panel__calc">
                              {formatMoney(row.base, sign)} {row.key === 'raw' ? 'в месяц (средн.)' : 'в месяц'} ×
                              <NumberInput
                                integer
                                max={120}
                                value={form[`months_${row.key}`]}
                                suffix="мес."
                                onChange={(v) => setMonths(row.key, v)}
                              />
                            </span>
                          )
                        ) : (
                          <span className="invest-sheet__source">Из списков на шаге 2</span>
                        )}
                      </td>
                      <td className="invest-sheet__num">{formatMoney(row.total, sign)}</td>
                      {withOwn && (
                        <td className="fm-panel__own">
                          <NumberInput value={form.own[row.key]} suffix={sign} onChange={(v) => setOwn(row.key, v)} />
                        </td>
                      )}
                      {mixed ? (
                        <>
                          <td className="invest-sheet__num">{formatMoney(investor, sign)}</td>
                          <td className="fm-panel__own">
                            <NumberInput value={form.credit?.[row.key]} suffix={sign} onChange={(v) => setCredit(row.key, v)} />
                          </td>
                        </>
                      ) : (
                        <td className="invest-sheet__num">{formatMoney(attracted, sign)}</td>
                      )}
                      <td className="invest-sheet__action">
                        <button type="button" className="btn btn--small" onClick={() => setDetailKey(row.key)}>
                          Детали
                        </button>
                      </td>
                    </tr>
                  )
                })}
              </tbody>
              <tfoot>
                <tr className="invest-sheet__total">
                  <td>Итого</td>
                  <td />
                  <td className="invest-sheet__num">{formatMoney(investmentTotal, sign)}</td>
                  {withOwn && <td className="invest-sheet__num">{formatMoney(ownTotal, sign)}</td>}
                  {mixed ? (
                    <>
                      <td className="invest-sheet__num">{formatMoney(investorTotal, sign)}</td>
                      <td className="invest-sheet__num">{formatMoney(creditTotal, sign)}</td>
                    </>
                  ) : (
                    <td className="invest-sheet__num">{formatMoney(attractedTotal, sign)}</td>
                  )}
                  <td />
                </tr>
              </tfoot>
            </table>
          </div>
        )}
      </SetupRow>

      {detailRow ? (
        <div className="drawer" role="presentation" onClick={() => setDetailKey('')}>
          <aside
            className="drawer__panel drawer__panel--invest"
            role="dialog"
            aria-modal="true"
            aria-labelledby="invest-detail-title"
            onClick={(event) => event.stopPropagation()}
          >
            <div className="drawer__body">
              <div className="drawer__head">
                <div>
                  <h2 id="invest-detail-title">Детали</h2>
                  <p className="muted">{detailRow.label}</p>
                </div>
                <button type="button" className="drawer__close" onClick={() => setDetailKey('')} aria-label="Закрыть">
                  ×
                </button>
              </div>

              <div className="invest-detail__summary">
                <div>
                  <span>В инвестиции</span>
                  <strong>{formatMoney(detailRow.total, sign)}</strong>
                </div>
                {withOwn ? (
                  <div>
                    <span>Свои</span>
                    <strong>{formatMoney(detailOwn, sign)}</strong>
                  </div>
                ) : null}
                {mixed ? (
                  <>
                    <div>
                      <span>Инвестор</span>
                      <strong>{formatMoney(detailInvestor, sign)}</strong>
                    </div>
                    <div>
                      <span>Кредит</span>
                      <strong>{formatMoney(detailCredit, sign)}</strong>
                    </div>
                  </>
                ) : (
                  <div>
                    <span>{attractedLabel}</span>
                    <strong>{formatMoney(detailAttracted, sign)}</strong>
                  </div>
                )}
              </div>

              {detailRow.operating ? (
                detailRow.stockListed ? (
                  <p className="invest-detail__formula">
                    Первоначальный товарный запас <strong>{formatMoney(detailRow.total, sign)}</strong>
                    {' — '}в ДДС один раз через ячейку запаса, без амортизации и без CAPEX.
                  </p>
                ) : detailRow.periodAware ? (
                  <p className="invest-detail__formula">
                    На {detailRow.months} мес. запуска с учётом периода оплаты ={' '}
                    <strong>{formatMoney(detailRow.total, sign)}</strong>
                    . Годовой платёж закладывается один раз на первые 12 месяцев, а не умножается на число месяцев.
                  </p>
                ) : (
                  <p className="invest-detail__formula">
                    {formatMoney(detailRow.base, sign)} в месяц × {detailRow.months} мес. ={' '}
                    <strong>{formatMoney(detailRow.total, sign)}</strong>
                  </p>
                )
              ) : (
                <p className="invest-detail__formula">Сумма из списков активов на шаге «Продажи».</p>
              )}

              {detailLines.length === 0 ? (
                <p className="sales-note">Позиций пока нет — сумма собрана без отдельных строк.</p>
              ) : (
                <ul className="invest-detail__list">
                  {detailLines.map((line, index) => (
                    <li key={`${line.name}-${index}`}>
                      <div>
                        <strong>{line.name}</strong>
                        {line.note ? <span className="muted">{line.note}</span> : null}
                      </div>
                      <b>{formatMoney(line.amount, sign)}</b>
                    </li>
                  ))}
                </ul>
              )}

              {detailRow.operating && detailLines.length > 0 && !detailRow.stockListed && !detailRow.periodAware ? (
                <p className="fm-field__hint">
                  В таблице выше в инвестиции идёт месячная сумма × число месяцев. Здесь позиции — за один месяц.
                </p>
              ) : null}
            </div>
          </aside>
        </div>
      ) : null}
    </div>
  )
}
