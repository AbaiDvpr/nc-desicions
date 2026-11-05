import {
  Checkbox,
  Field,
  Grid,
  ListEditor,
  MonthChips,
  NumberInput,
  Section,
  Select,
  TextInput,
} from './fields.jsx'
import { bindFields, formatMoney, num, panelRows, yearsBetween } from './helpers.js'
import {
  CURRENCIES,
  CURRENCY_SIGNS,
  EXPENSE_TYPES,
  FINANCING,
  INVEST_FIELDS,
  LEGAL_FORMS,
  MONTH_OPTIONS,
  PERIODS,
  SCHEDULES,
  TAX_REGIMES,
  TIME_PARAMS,
  UNITS,
  YES_NO,
  newAsset,
  newExpense,
  newSalary,
  newVariableCost,
  newVolume,
} from './options.js'

function AssetList({ value, onChange, sign }) {
  return (
    <ListEditor
      value={value}
      onChange={onChange}
      create={newAsset}
      addLabel="Добавить строку"
      emptyText="Пока пусто"
      title={(item, index) => item.name || `Строка ${index + 1}`}
    >
      {(item, _set, _index, patch) => (
        <Grid>
          <Field label="Наименование" wide>
            <TextInput
              value={item.name}
              onChange={(v) => patch({ name: v })}
              placeholder="укажите название"
            />
          </Field>
          <Field label="Кол-во">
            <NumberInput
              integer
              value={item.count}
              onChange={(v) => patch({ count: v, price: num(v) * num(item.price_one) })}
            />
          </Field>
          <Field label="Цена за ед">
            <NumberInput
              value={item.price_one}
              suffix={sign}
              onChange={(v) => patch({ price_one: v, price: num(item.count) * num(v) })}
            />
          </Field>
          <Field label="Стоимость">
            <NumberInput value={item.price} suffix={sign} onChange={(v) => patch({ price: v })} />
          </Field>
          <Field label="Год приобретения">
            <NumberInput integer value={item.year} onChange={(v) => patch({ year: v })} placeholder="2026" />
          </Field>
          <Field label="Срок службы в годах">
            <NumberInput integer value={item.life_years} onChange={(v) => patch({ life_years: v })} />
          </Field>
        </Grid>
      )}
    </ListEditor>
  )
}

function ExpenseList({ value, onChange, sign, withType }) {
  return (
    <ListEditor
      value={value}
      onChange={onChange}
      create={() => newExpense()}
      addLabel="Добавить расход"
      title={(item, index) => item.name || `Расход ${index + 1}`}
    >
      {(item, set) => (
        <Grid>
          <Field label="Наименование">
            <TextInput value={item.name} onChange={(v) => set('name', v)} />
          </Field>
          <Field label="Сумма">
            <NumberInput integer value={item.value} suffix={sign} onChange={(v) => set('value', v)} />
          </Field>
          <Field label="Период списания">
            <Select value={item.period} onChange={(v) => set('period', v)} options={PERIODS} />
          </Field>
          {withType && (
            <Field label="Тип расходов">
              <Select value={item.type_expense} onChange={(v) => set('type_expense', v)} options={EXPENSE_TYPES} />
            </Field>
          )}
          <Field label="Инвестиционный период" group>
            <Checkbox value={item.invest} onChange={(v) => set('invest', v)} label="На инвест период" />
          </Field>
        </Grid>
      )}
    </ListEditor>
  )
}

function AmountFields({ value, onChange, sign }) {
  const set = (key, next) => onChange({ ...value, [key]: next })
  return (
    <Grid>
      {INVEST_FIELDS.map(([key, label]) => (
        <Field key={key} label={label}>
          <NumberInput value={value[key]} suffix={sign} onChange={(v) => set(key, v)} placeholder="укажите сумму" />
        </Field>
      ))}
    </Grid>
  )
}

export function CompanyStep({ form, set }) {
  const f = bindFields(form, set)
  return (
    <Section title="Основные параметры" description="Эти данные задают валюту, налоги и горизонт расчёта.">
      <Grid>
        <Field label="Название идеи (проекта)" required wide>
          <TextInput {...f('name')} required maxLength={500} placeholder="Например, Кофейня «Бариста»" />
        </Field>
        <Field label="Организационно-правовая форма" required>
          <Select {...f('legal_form')} options={LEGAL_FORMS} />
        </Field>
        <Field label="С учетом НДС" required hint="Учитывается НДС при заполнении проекта">
          <Select {...f('nds')} options={YES_NO} />
        </Field>
        <Field label="Форма налогообложения" required>
          <Select {...f('tax_regime')} options={TAX_REGIMES} />
        </Field>
        <Field label="В какой валюте, единице измерения произвести расчет" required>
          <Select {...f('currency')} options={CURRENCIES} />
        </Field>
        <Field label="Временные параметры расчета" required>
          <Select {...f('time_param')} options={TIME_PARAMS} />
        </Field>
        <Field label="График работы" required>
          <Select {...f('schedule')} options={SCHEDULES} />
        </Field>
        <Field label="Дата начала проекта" required hint="Дата начала и окончания планирования по проекту.">
          <input type="month" required value={form.planning_from} onChange={(e) => set('planning_from', e.target.value)} />
        </Field>
        <Field label="Дата окончания проекта" required>
          <input type="month" required value={form.planning_to} onChange={(e) => set('planning_to', e.target.value)} />
        </Field>
      </Grid>
    </Section>
  )
}

export function SalesStep({ form, set }) {
  const sign = CURRENCY_SIGNS[form.currency]
  const years = yearsBetween(form.planning_from, form.planning_to)
  const setCapacity = (year, value) => set('planned_capacity', { ...form.planned_capacity, [year]: value })

  return (
    <>
      <Section
        title="На каких условиях вы планируете привлечь инвестиции?"
        description="При долевом финансировании привлекаемые средства инвестора учитываются как взносы в уставный капитал."
      >
        <Grid>
          <Field label="Вид финансирования" required wide>
            <Select value={form.financing} onChange={(v) => set('financing', v)} options={FINANCING} />
          </Field>
          {Number(form.financing) === 1 && (
            <>
              <Field label="Введите срок участия инвестора в проекте" required>
                <NumberInput
                  integer
                  required
                  max={100}
                  value={form.investor_term}
                  suffix="лет"
                  onChange={(v) => set('investor_term', v)}
                  placeholder="в годах"
                />
              </Field>
              <Field label="Укажите % дивидендов инвестора" required>
                <NumberInput
                  required
                  max={100}
                  value={form.investor_dividend}
                  suffix="%"
                  onChange={(v) => set('investor_dividend', v)}
                  placeholder="укажите в процентах"
                />
              </Field>
            </>
          )}
          {Number(form.financing) === 2 && (
            <>
              <Field
                label="Укажите срок займа, на инвестиционные цели"
                required
                wide
                hint="К инвестиционным целям относятся приобретение помещения, оборудования, ремонт помещения, строительство"
              >
                <NumberInput
                  integer
                  required
                  max={250}
                  value={form.loan_term}
                  suffix="мес."
                  onChange={(v) => set('loan_term', v)}
                  placeholder="в месяцах"
                />
              </Field>
              <Field label="Выберите льготный период по выплате основного долга" hint="Льготный период — «каникулы» по выплате основного долга">
                <NumberInput
                  integer
                  max={36}
                  value={form.loan_grace}
                  suffix="мес."
                  onChange={(v) => set('loan_grace', v)}
                  placeholder="в месяцах (не более 36 месяцев)"
                />
              </Field>
              <Field label="Годовая ставка вознаграждения по кредиту" required>
                <NumberInput
                  required
                  max={100}
                  value={form.loan_rate}
                  suffix="%"
                  onChange={(v) => set('loan_rate', v)}
                  placeholder="укажите в процентах"
                />
              </Field>
              <Field label="График" group>
                <Checkbox value={form.loan_annuity} onChange={(v) => set('loan_annuity', v)} label="Аннуитетный расчет" />
              </Field>
              <Field
                label="Укажите срок займа на пополнение оборотных средств"
                wide
                hint="Срок займа на пополнение оборотных средств составляет от 24 до 36 месяцев"
              >
                <NumberInput
                  integer
                  min={24}
                  max={36}
                  value={form.wc_term}
                  suffix="мес."
                  onChange={(v) => set('wc_term', v)}
                  placeholder="в месяцах"
                />
              </Field>
              <Field label="Укажите льготный период (каникулы)" hint="Льготный период — «каникулы» по выплате основного долга">
                <NumberInput
                  integer
                  max={36}
                  value={form.wc_grace}
                  suffix="мес."
                  onChange={(v) => set('wc_grace', v)}
                  placeholder="в месяцах (не более 36 месяцев)"
                />
              </Field>
              <Field label="Годовая ставка вознаграждения по кредиту">
                <NumberInput
                  max={100}
                  value={form.wc_rate}
                  suffix="%"
                  onChange={(v) => set('wc_rate', v)}
                  placeholder="укажите в процентах"
                />
              </Field>
            </>
          )}
          {Number(form.financing) === 3 && (
            <Field label="Грант" wide>
              <span className="fm-field__hint">Для гранта дополнительные условия на этом шаге не заполняются.</span>
            </Field>
          )}
        </Grid>
      </Section>

      <Section title="Сроки продаж" description="Месяц и год начала продаж и выхода на 100% план.">
        <Grid>
          <Field label="Дата начала продаж/производства" required hint="Тут необходимо указать дату, когда Вы планируете начать продажи (производство)">
            <input
              type="month"
              required
              value={form.sales_start}
              min={form.planning_from}
              max={form.planning_to}
              onChange={(e) => set('sales_start', e.target.value)}
            />
          </Field>
          <Field
            label="Срок выхода на плановый объем продаж/производства"
            required
            hint="Тут необходимо указать дату, когда вы планируете выйти на 100% объем продаж (производства)"
          >
            <input
              type="month"
              required
              value={form.planned_volume}
              min={form.planning_from}
              max={form.planning_to}
              onChange={(e) => set('planned_volume', e.target.value)}
            />
            <span className="fm-field__hint">при выполнении 100% плана</span>
          </Field>
        </Grid>
      </Section>

      <Section title="Планируемый объем продаж в месяц (при выполнении 100% плана) количество">
        <ListEditor
          value={form.volume_sales}
          onChange={(rows) => set('volume_sales', rows)}
          create={newVolume}
          addLabel="Добавить продукцию"
          title={(item, index) => item.name || `Позиция ${index + 1}`}
        >
          {(item, setRow) => (
            <Grid>
              <Field label="Наименование продукции/услуг объединённых в группы" required wide>
                <TextInput required value={item.name} onChange={(v) => setRow('name', v)} />
              </Field>
              <Field label="Планируемое количество продаж за период" required>
                <NumberInput integer required value={item.count} onChange={(v) => setRow('count', v)} />
              </Field>
              <Field label="Ед. измерения" required>
                <Select value={item.units} onChange={(v) => setRow('units', v)} options={UNITS} />
              </Field>
              <Field label="Цена за ед./ср.чек" required>
                <NumberInput required value={item.price} suffix={sign} onChange={(v) => setRow('price', v)} />
              </Field>
              <Field label="Период продаж" wide group>
                <Checkbox value={item.seasonal} onChange={(v) => setRow('seasonal', v)} label="Указать сезонность" />
                {item.seasonal && (
                  <MonthChips value={item.months} onChange={(v) => setRow('months', v)} options={MONTH_OPTIONS} />
                )}
              </Field>
            </Grid>
          )}
        </ListEditor>
      </Section>

      <Section
        title="Проектная мощность"
        description="Если не указывать, то план выхода на проектную 100% мощность будет равномерно распределяться на ежемесячной основе."
      >
        {years.length === 0 ? (
          <p className="muted">Сначала укажите период планирования на шаге 1.</p>
        ) : (
          <Grid>
            {years.map((year) => (
              <Field key={year} label={String(year)}>
                <NumberInput
                  integer
                  max={1000}
                  suffix="%"
                  value={form.planned_capacity[year] ?? ''}
                  onChange={(v) => setCapacity(year, v)}
                />
              </Field>
            ))}
          </Grid>
        )}
      </Section>

      <Section title="Прочие доходы (при наличии)" description="Укажите дополнительные доходы одной суммой, если таковые имеются">
        <Grid>
          <Field label="Сумма в месяц">
            <NumberInput integer value={form.other_income} suffix={sign} onChange={(v) => set('other_income', v)} placeholder="сумма в месяц" />
          </Field>
        </Grid>
      </Section>

      <Section title="Перечень приобретаемого оборудования (при наличии данной информации)">
        <AssetList value={form.equipment} onChange={(rows) => set('equipment', rows)} sign={sign} />
      </Section>
      <Section title="Перечень приобретаемой недвижимости (при наличии данной информации)">
        <AssetList value={form.premises} onChange={(rows) => set('premises', rows)} sign={sign} />
      </Section>
      <Section title="Перечень видов работ СМР, капитального ремонта">
        <AssetList value={form.smr} onChange={(rows) => set('smr', rows)} sign={sign} />
      </Section>
      <Section title="Перечень материалов при СМР, капитальном ремонте">
        <AssetList value={form.overhaul} onChange={(rows) => set('overhaul', rows)} sign={sign} />
      </Section>
    </>
  )
}

export function ExpensesStep({ form, set }) {
  const sign = CURRENCY_SIGNS[form.currency]
  const productOptions = form.volume_sales
    .map((row) => String(row.name || '').trim())
    .filter(Boolean)
    .map((name) => [name, name])

  return (
    <>
      <Section
        title="Себестоимость на единицу продукции / переменные расходы"
        description="Если есть калькуляция на единицу — заполните первую таблицу. Если нет — переключитесь на переменные расходы в месяц."
      >
        <Grid>
          <Field label="Как заполняете себестоимость" required wide>
            <Select
              value={form.cost_mode}
              onChange={(v) => set('cost_mode', v)}
              options={[
                ['unit', 'Себестоимость на единицу продукции'],
                ['monthly', 'Переменные расходы в месяц'],
              ]}
            />
          </Field>
        </Grid>
        {form.cost_mode === 'unit' ? (
          productOptions.length === 0 ? (
            <p className="muted">Сначала заполните объём продаж на шаге 2.</p>
          ) : (
            <ListEditor
              value={form.variable_costs}
              onChange={(rows) => set('variable_costs', rows)}
              create={newVariableCost}
              addLabel="Добавить затрату"
              title={(item, index) => item.cost_type || item.product || `Затрата ${index + 1}`}
            >
              {(item, setRow) => (
                <Grid>
                  <Field label="Наименование продукции" required>
                    <Select required value={item.product} onChange={(v) => setRow('product', v)} options={[['', 'Выберите'], ...productOptions]} />
                  </Field>
                  <Field label="Вид затрат" required>
                    <TextInput required value={item.cost_type} onChange={(v) => setRow('cost_type', v)} />
                  </Field>
                  <Field label="Стоимость затрат" required>
                    <NumberInput integer required value={item.price} suffix={sign} onChange={(v) => setRow('price', v)} />
                  </Field>
                </Grid>
              )}
            </ListEditor>
          )
        ) : (
          <ExpenseList value={form.variable_other} onChange={(rows) => set('variable_other', rows)} sign={sign} />
        )}
      </Section>

      <Section
        title="Фонд заработной платы"
        description="Процент от дохода — процент от реализации товаров и услуг, выбранных в поле «Услуги». ПП — производственный персонал, связанный с производством продукции, товаров и услуг."
      >
        <ListEditor
          value={form.salary}
          onChange={(rows) => set('salary', rows)}
          create={newSalary}
          addLabel="Добавить должность"
          emptyText="Штат пока не указан"
          title={(item, index) => item.name || `Должность ${index + 1}`}
        >
          {(item, setRow) => (
            <Grid>
              <Field label="На инвест период" group>
                <Checkbox value={item.invest} onChange={(v) => setRow('invest', v)} label="На инвест период" />
              </Field>
              <Field label="Наименование должности" required>
                <TextInput required value={item.name} onChange={(v) => setRow('name', v)} />
              </Field>
              <Field label="Количество" required>
                <NumberInput integer required value={item.count} onChange={(v) => setRow('count', v)} />
              </Field>
              <Field label="Оклад в месяц" required>
                <NumberInput integer required value={item.salary} suffix={sign} onChange={(v) => setRow('salary', v)} />
              </Field>
              <Field label="Процент от дохода">
                <NumberInput integer max={100} value={item.percent} suffix="%" onChange={(v) => setRow('percent', v)} />
              </Field>
              <Field label="ПП" required>
                <Select value={item.industrial} onChange={(v) => setRow('industrial', v)} options={YES_NO} />
              </Field>
              <Field label="Услуги" wide group>
                {productOptions.length === 0 ? (
                  <span className="fm-field__hint">Сначала заполните объём продаж на шаге 2.</span>
                ) : (
                  <MonthChips value={item.services} onChange={(v) => setRow('services', v)} options={productOptions} />
                )}
              </Field>
            </Grid>
          )}
        </ListEditor>
      </Section>

      <Section title="Постоянные расходы" description="Расходы, которые вы несёте на постоянной основе.">
        <h3 className="fm-subtitle">Административные расходы</h3>
        <p className="muted">Укажите расходы, которые Вы несете на постоянной основе в месяц</p>
        <ExpenseList withType value={form.admin_expenses} onChange={(rows) => set('admin_expenses', rows)} sign={sign} />
        <h3 className="fm-subtitle">Маркетинговые расходы</h3>
        <p className="muted">Укажите расходы, связанные с продвижением Ваших товаров/услуг</p>
        <ExpenseList value={form.marketing_expenses} onChange={(rows) => set('marketing_expenses', rows)} sign={sign} />
      </Section>
    </>
  )
}

export function InvestmentStep({ form, set }) {
  const sign = CURRENCY_SIGNS[form.currency]
  const rows = panelRows(form)
  const setMonths = (key, value) => set(`months_${key}`, value)

  return (
    <>
      <Section
        title="Суммы, которые нужно распределить"
        description="К оборотному капиталу относятся затраты на сырьё, товары, расходные материалы, фонд заработной платы, рекламу, административные и прочие расходы."
      >
        {rows.length === 0 ? (
          <p className="muted">На предыдущих шагах пока нет сумм для распределения.</p>
        ) : (
          <div className="table-wrap">
            <table className="fm-panel">
              <thead>
                <tr>
                  <th>Вид затрат</th>
                  <th>Распределено</th>
                  <th>Всего инвестиций</th>
                </tr>
              </thead>
              <tbody>
                {rows.map((row) => {
                  const mismatch = !row.operating && Math.round(row.distributed) !== Math.round(row.total)
                  return (
                    <tr key={row.key} className={mismatch ? 'fm-panel--bad' : ''}>
                      <td>{row.label}</td>
                      <td>{formatMoney(row.distributed, sign)}</td>
                      <td>
                        {row.operating ? (
                          <span className="fm-panel__calc">
                            {formatMoney(row.base, sign)} ×
                            <NumberInput
                              integer
                              max={120}
                              value={form[`months_${row.key}`]}
                              suffix="мес."
                              onChange={(v) => setMonths(row.key, v)}
                            />
                            = {formatMoney(row.total, sign)}
                          </span>
                        ) : (
                          formatMoney(row.total, sign)
                        )}
                      </td>
                    </tr>
                  )
                })}
              </tbody>
            </table>
          </div>
        )}
      </Section>

      <Section
        title="Будете ли вы участвовать собственными вложениями?"
        description="Если да, укажите, на что будут потрачены собственные средства."
      >
        <Grid>
          <Field label="Собственные вложения" required>
            <Select value={form.own_invest} onChange={(v) => set('own_invest', v)} options={YES_NO} />
          </Field>
        </Grid>
        {Number(form.own_invest) === 1 && (
          <AmountFields value={form.own} onChange={(v) => set('own', v)} sign={sign} />
        )}
      </Section>

      <Section
        title="Распределите привлекаемую сумму инвестиций по видам затрат"
        description="Необходимо указать, на какие цели привлекаются инвестиции."
      >
        <AmountFields value={form.attracted} onChange={(v) => set('attracted', v)} sign={sign} />
      </Section>
    </>
  )
}
