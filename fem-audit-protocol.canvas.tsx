import {
  Callout,
  Card,
  CardBody,
  CardHeader,
  Divider,
  Grid,
  H1,
  H2,
  H3,
  Pill,
  Row,
  Stack,
  Stat,
  Table,
  Text,
  useHostTheme,
} from 'cursor/canvas'

const AUDIT_DATE = '2026-11-10'
const LAW_AS_OF = '2026-01-01'
const LAW_SOURCE = 'nalogikz.kz / Базовые ставки на 2026 год; уточнения КГД МФ РК (prg.kz, 22.09.2025)'

type Tone = 'neutral' | 'success' | 'warning' | 'danger' | 'info'

const rates: {
  name: string
  law: string
  product: string
  status: Tone
  note: string
}[] = [
  {
    name: 'МЗП',
    law: '85 000 ₸ (2025–2026)',
    product: 'FEM_MZP: 2024:85000 (наследует на 2025–2026)',
    status: 'success',
    note: 'Совпадает с законом при дефолте config',
  },
  {
    name: 'МРП',
    law: '4 325 ₸ (2026)',
    product: 'Не используется в FEM v5',
    status: 'warning',
    note: 'Нужен для порогов НДС/соцналога; в модели нет справочника МРП',
  },
  {
    name: 'ОПВ',
    law: '10%, потолок 50 МЗП',
    product: '10%, MIN(база, 50×МЗП)',
    status: 'success',
    note: 'Формула ФОТ совпадает',
  },
  {
    name: 'ОПВР',
    law: '2026: 3,5%; 2027: 4,5%; с 2028: 5%',
    product: 'Лестница 2024→1,5% … 2026→3,5% … 2028→5%',
    status: 'success',
    note: 'График в коде совпадает с поэтапным вводом',
  },
  {
    name: 'СО (соц. отчисления)',
    law: '5% с 2025; база 1–7 МЗП после вычета ОПВ',
    product: '5% с 2025 (ранее 3,5%); база 1–7 МЗП',
    status: 'success',
    note: 'Совпадает с комментарием и формулой листа ФОТ',
  },
  {
    name: 'ООСМС',
    law: '3%; потолок с 2026 — 40 МЗП',
    product: '3% с 2023; потолок 10 МЗП до 2025, 40 МЗП с 2026',
    status: 'success',
    note: 'Совпадает',
  },
  {
    name: 'ВОСМС',
    law: '2% с работника (потолок 20 МЗП)',
    product: 'Не удерживается в FEM',
    status: 'danger',
    note: 'В Excel нет колонки ВОСМС — чистый ФОТ завышен для работника/занижены удержания',
  },
  {
    name: 'Социальный налог',
    law: 'ТОО 6% (2026); с/х 1,8%',
    product: 'Не моделируется отдельно',
    status: 'danger',
    note: 'Есть СО 5%, но не соцналог 6% для юрлиц на ОУР',
  },
  {
    name: 'КПН',
    law: '20% (база); отраслевые 3/5/6/25%',
    product: 'ТОО + ОУР → 20%',
    status: 'success',
    note: 'Базовая ставка ОК_RATES[2][1]=0.20; отраслевые льготы не в анкете',
  },
  {
    name: 'ИПН (ОУР)',
    law: 'с 2026: 10% до порога + 15% свыше',
    product: 'ИП + ОУР → плоские 10%',
    status: 'warning',
    note: 'Прогрессия 15% не заложена',
  },
  {
    name: 'Упрощёнка (СНР)',
    law: '4% КПН/ИПН (маслихат ±50%)',
    product: 'TAX_RATES … regime 4 → 3%',
    status: 'danger',
    note: 'Расхождение: в продукте 3%, в НК с 2026 — 4%',
  },
  {
    name: 'НДС',
    law: '16% с 2026 (было 12%)',
    product: 'Флаг nds в анкете; ставка 16% в расчёте выручки/затрат не прошита как 2026',
    status: 'danger',
    note: 'Нужна явная ставка НДС 16% и влияние на ДДС; сейчас в основном учёт «плательщик да/нет»',
  },
]

const matrix: {
  id: string
  project: string
  tax: string
  fin: string
  biz: string
  modelId: string | null
  cover: string
}[] = [
  {
    id: 'T1',
    project: 'ИП + упрощёнка + кредит + сезонный agri',
    tax: 'Упрощёнка / ИП',
    fin: 'Кредит',
    biz: 'С/х / сезон',
    modelId: '18',
    cover: 'Субсидия Damu, сезонность, DSCR, сырьё',
  },
  {
    id: 'T2',
    project: 'ТОО + ОУР + кредит + услуги (НДС)',
    tax: 'ОУР / ТОО + НДС',
    fin: 'Кредит',
    biz: 'Услуги',
    modelId: '17',
    cover: 'КПН 20%, ФОТ АУП, CAPEX, график',
  },
  {
    id: 'T3',
    project: 'ТОО + ОУР + инвестор',
    tax: 'ОУР / ТОО',
    fin: 'Инвестор',
    biz: 'Гостиница',
    modelId: '12',
    cover: 'WACC инвестор, дивиденды, горизонт',
  },
  {
    id: 'T4',
    project: 'ТОО + упрощёнка + кредит (внутренний эталон)',
    tax: 'Упрощёнка / ТОО',
    fin: 'Кредит',
    biz: 'Медицина',
    modelId: '16',
    cover: '«Радуга» — регрессия формул FEM',
  },
  {
    id: 'T5',
    project: 'ТОО + упрощёнка + инвестор+кредит',
    tax: 'Упрощёнка / ТОО',
    fin: 'Смешанное',
    biz: 'Услуги / IT',
    modelId: '10',
    cover: 'Два источника капитала, распределение CAPEX',
  },
  {
    id: 'T6',
    project: 'ТОО + ОУР + грант',
    tax: 'ОУР / ТОО',
    fin: 'Грант',
    biz: 'Спорт / ипподром',
    modelId: '11',
    cover: 'Грант в структуре финансирования, без долга',
  },
  {
    id: 'T7',
    project: 'ИП + упрощёнка + инвестор, без НДС',
    tax: 'Упрощёнка / ИП',
    fin: 'Инвестор',
    biz: 'Ритейл / e-com',
    modelId: '7',
    cover: 'ИПН/упрощёнка для ИП, долевое',
  },
  {
    id: 'T8',
    project: 'ТОО + ОУР + кредит, производство + НДС',
    tax: 'ОУР / ТОО + НДС',
    fin: 'Кредит',
    biz: 'Производство',
    modelId: '9',
    cover: 'ФОТ производства, себестоимость, НДС-флаг',
  },
]

const protocol: {
  test: string
  checked: string
  found: string
  fixed: string
  status: Tone
}[] = [
  {
    test: 'T1 · #18 Apple Garden',
    checked: 'Субсидия, график %, WACC, сезон «в месяц», DSCR',
    found: 'subsidy_term=0 → Excel 19,75% vs анкета 12,6%; сезон суммировал год как месяц; DSCR месячный −2',
    fixed: '2026-11-10: автосрок субсидии, единый loanSketch, WACC по факту, avg month, DSCR по годам',
    status: 'success',
  },
  {
    test: 'T2 · #17 Отель',
    checked: 'Адм. расходы + флаг invest; даты активов',
    found: 'invest трактовался как «только до продаж» (не как в Радуге); узкая дата оплаты',
    fixed: '2026-11-10: invest = с начала проекта и далее; ширина дат в активах',
    status: 'success',
  },
  {
    test: 'T4 · #16 Радуга (внутренний эталон)',
    checked: 'Регрессия формул FEM vs старый PHP/Радуга',
    found: 'Используется как эталон логики, не как банковский пакет',
    fixed: 'Не трогали эталонные BpJob; логика invest/адм выровнена',
    status: 'info',
  },
  {
    test: 'Ставки НК 2026',
    checked: 'Сверка TAX_RATES, ФОТ, МЗП с nalogikz / КГД',
    found: 'Упрощёнка в коде 3% vs закон 4%; нет ВОСМС, соцналога 6%, НДС 16%',
    fixed: 'Не в этом проходе — зафиксировано как обязательный бэклог',
    status: 'danger',
  },
  {
    test: 'Банковский эталон',
    checked: 'Поиск принятой банком ручной модели в репозитории/БД',
    found: 'Внешнего файла «принято банком» нет; #16 — только внутренний эталон',
    fixed: 'Требуется 1–2 Excel от заказчика с отметкой банка',
    status: 'warning',
  },
  {
    test: 'T3/T5/T6/T7/T8',
    checked: 'Покрытие матрицы готовыми id',
    found: 'Модели в БД есть; полный прогон Excel+протокол по каждому — следующий цикл',
    fixed: 'Матрица назначена; исполнение — очередь',
    status: 'warning',
  },
]

function tonePill(status: Tone): { label: string; tone: Tone } {
  if (status === 'success') return { label: 'OK', tone: 'success' }
  if (status === 'danger') return { label: 'GAP', tone: 'danger' }
  if (status === 'warning') return { label: 'Risk', tone: 'warning' }
  return { label: 'Note', tone: 'info' }
}

export default function FemAuditProtocol() {
  const theme = useHostTheme()
  const gaps = rates.filter((r) => r.status === 'danger').length
  const ok = rates.filter((r) => r.status === 'success').length
  const covered = matrix.filter((m) => m.modelId).length

  return (
    <Stack gap={20} style={{ padding: 20, maxWidth: 960 }}>
      <Stack gap={6}>
        <H1>Аудит FEM — протокол</H1>
        <Text tone="secondary" size="small">
          Дата аудита {AUDIT_DATE} · ставки закона на {LAW_AS_OF} · источник: {LAW_SOURCE}
        </Text>
      </Stack>

      <Grid columns={4} gap={12}>
        <Stat value={String(covered)} label="Тестов в матрице" tone="info" />
        <Stat value={String(ok)} label="Ставок совпало" tone="success" />
        <Stat value={String(gaps)} label="Критичных GAP по ставкам" tone="danger" />
        <Stat value="1" label="Банк. эталон в наличии" tone="warning" />
      </Grid>

      <Callout tone="warning" title="Эталон банка">
        В продукте есть внутренний эталон «Радуга» (#16). Файла, официально принятого банком, в репозитории нет.
        Для закрытия требования руководителя нужны 1–2 ручные модели с актом/письмом банка — иначе расхождения
        с «банковской правдой» нельзя закрыть формально.
      </Callout>

      <H2>1. Матрица тестов (режим × финансирование × бизнес)</H2>
      <Text tone="secondary" size="small">
        8 кейсов покрывают ИП/ТОО, ОУР/упрощёнку, кредит/инвестор/грант/смесь, сезон/услуги/производство/гостеприимство.
        Id — готовые модели в БД кабинета.
      </Text>
      <Table
        headers={['ID', 'Кейс', 'Налог', 'Финансы', 'Бизнес', 'Model', 'Фокус']}
        rows={matrix.map((m) => [
          m.id,
          m.project,
          m.tax,
          m.fin,
          m.biz,
          m.modelId ? `#${m.modelId}` : '—',
          m.cover,
        ])}
      />

      <H2>2. Справочник ставок: закон vs продукт</H2>
      <Text tone="secondary" size="small">
        Сверка только по опубликованным справочникам на {LAW_AS_OF}, не по памяти. Код: fem_v5_book.py / TAX_RATES / лист ФОТ.
      </Text>
      <Table
        headers={['Ставка', 'Закон 2026', 'В FEM', 'Статус', 'Комментарий']}
        rows={rates.map((r) => {
          const p = tonePill(r.status)
          return [r.name, r.law, r.product, p.label, r.note]
        })}
        rowTone={rates.map((r) => (r.status === 'danger' ? 'danger' : r.status === 'warning' ? 'warning' : undefined))}
      />

      <Stack gap={8}>
        <H3>Критичные расхождения</H3>
        <Card>
          <CardHeader>Упрощёнка 3% в коде vs 4% в НК</CardHeader>
          <CardBody>
            <Text size="small">
              TAX_RATES: ИП/ТОО regime=4 → 0.03. С 01.01.2026 СНР упрощённая декларация — 4% (маслихат ±50%).
              Любая модель на упрощёнке занижает налог на ~25% относительно базовой ставки закона.
            </Text>
          </CardBody>
        </Card>
        <Card>
          <CardHeader>НДС 16%, ВОСМС 2%, соцналог 6%</CardHeader>
          <CardBody>
            <Text size="small">
              В анкете есть флаг НДС, но ставка 16% и полный контур удержаний/начислений по ВОСМС и соцналогу в FEM v5
              не доведены. Для банковских пакетов на ОУР/ТОО это видимый пробел кассового потока и чистой зарплаты.
            </Text>
          </CardBody>
        </Card>
      </Stack>

      <H2>3. Протокол прогонов</H2>
      <Text tone="secondary" size="small">
        Формат: что проверено → что найдено → что исправлено. Показывает системность для руководителя.
      </Text>
      <Table
        headers={['Тест', 'Проверено', 'Найдено', 'Исправлено', 'Статус']}
        rows={protocol.map((p) => [p.test, p.checked, p.found, p.fixed, tonePill(p.status).label])}
        rowTone={protocol.map((p) =>
          p.status === 'danger' ? 'danger' : p.status === 'warning' ? 'warning' : p.status === 'success' ? 'success' : undefined,
        )}
      />

      <Divider />

      <H2>4. Очередь после аудита</H2>
      <Stack gap={6}>
        <Row gap={8} align="center">
          <Pill tone="warning">P0</Pill>
          <Text>Упрощёнка 3% → 4% (с опцией маслихата) + регресс T1/T4/T7</Text>
        </Row>
        <Row gap={8} align="center">
          <Pill tone="warning">P0</Pill>
          <Text>НДС 16% в денежном потоке при nds=1</Text>
        </Row>
        <Row gap={8} align="center">
          <Pill>P1</Pill>
          <Text>ВОСМС 2% + соцналог 6% (ТОО ОУР); справочник МРП</Text>
        </Row>
        <Row gap={8} align="center">
          <Pill>P1</Pill>
          <Text>Получить 1–2 банковских эталона; завести эталонный diff-протокол</Text>
        </Row>
        <Row gap={8} align="center">
          <Pill tone="info">P2</Pill>
          <Text>Полный Excel-прогон T3, T5, T6, T7, T8 с записью в этот протокол</Text>
        </Row>
      </Stack>

      <Text tone="secondary" size="small" style={{ marginTop: 8 }}>
        Workspace: NC-consulting-NEW · лист ставок в fem_v5_book.py · модели 7–18 в PostgreSQL · источники ставок открыты{' '}
        {AUDIT_DATE}
      </Text>
    </Stack>
  )
}
