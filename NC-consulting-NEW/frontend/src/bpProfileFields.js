const field = (key, label, max, example) => ({ key, label, max, placeholder: example ? `Например: ${example}` : '' })

export const COMPANY_TABS = [
  {
    key: 'main',
    title: 'Регистрация',
    groups: [
      {
        fields: [
          field('company_name', 'Полное наименование', 200, 'ТОО «Радуга здоровья»'),
          field('bin', 'БИН / ИИН', 20, '180740000812'),
          field('registered', 'Дата регистрации', 40, '16.07.2018'),
          field('registration_doc', 'Учредительные документы', 600, 'типовой устав от 2018 года'),
          field('owners', 'Собственники и доли', 600, 'Иванов И. И. — 100%'),
          field('director', 'Руководитель', 200, 'Иванов Иван Иванович'),
          field('activity', 'Вид деятельности и ОКЭД', 300, 'медицинские услуги, ОКЭД 86220'),
          field('address', 'Адрес', 300, 'г. Астана, ул. Кенесары, 40'),
        ],
      },
    ],
    lists: [],
  },
  {
    key: 'about',
    title: 'О компании',
    groups: [
      {
        long: true,
        fields: [
          field('about', 'Чем занимается компания', 3000),
          field('experience', 'Опыт в похожих проектах', 3000),
          field('reputation', 'Деловая репутация', 2000, 'отзывы, награды, судебных споров нет'),
          field('credit_history', 'Кредитная история', 2000, 'действующих кредитов нет, просрочек не было'),
        ],
      },
    ],
    lists: [],
  },
  {
    key: 'team',
    title: 'Команда и обороты',
    groups: [{ wide: true, fields: [field('staff_now', 'Сотрудников сейчас', 300, '4 врача, 2 администратора')] }],
    lists: [
      {
        key: 'managers',
        title: 'Руководители и ключевые сотрудники',
        columns: [
          { key: 'name', label: 'ФИО', max: 120 },
          { key: 'position', label: 'Должность', max: 120 },
          { key: 'education', label: 'Образование', max: 300 },
          { key: 'experience', label: 'Опыт', max: 300 },
        ],
      },
      {
        key: 'turnover',
        title: 'Обороты за прошлые периоды',
        columns: [
          { key: 'period', label: 'Период', placeholder: 'Период, например 2024 год', max: 80 },
          { key: 'amount', label: 'Оборот', placeholder: 'Оборот, тенге', max: 40 },
        ],
      },
    ],
  },
  {
    key: 'market',
    title: 'Рынок и поставки',
    groups: [
      {
        long: true,
        fields: [
          field('licenses', 'Лицензии и разрешения', 1500),
          field('suppliers', 'Основные поставщики', 1500),
        ],
      },
    ],
    lists: [
      {
        key: 'competitors',
        title: 'Конкуренты',
        columns: [
          { key: 'name', label: 'Название', max: 160 },
          { key: 'format', label: 'Формат', max: 160 },
          { key: 'strong', label: 'Сильные стороны', max: 300 },
          { key: 'weak', label: 'Слабые стороны', max: 300 },
        ],
      },
    ],
  },
]

export const COMPANY_GROUPS = COMPANY_TABS.flatMap((tab) => tab.groups)
export const COMPANY_LISTS = COMPANY_TABS.flatMap((tab) => tab.lists)
export const COMPANY_TEXT_KEYS = COMPANY_GROUPS.flatMap((group) => group.fields.map((item) => item.key))

export const PROJECT_GROUPS = [
  { wide: true, fields: [field('project_address', 'Адрес объекта', 300, 'г. Астана, ул. Шәмші Қалдаяқов, 5')] },
  { long: true, fields: [field('contracts', 'Контракты и предварительные договоры', 1500)] },
]

export const PROJECT_LISTS = [
  {
    key: 'collateral',
    title: 'Залог (для кредита)',
    columns: [
      { key: 'name', label: 'Что в залоге', max: 300 },
      { key: 'owner', label: 'Залогодатель', max: 160 },
      { key: 'value', label: 'Рыночная стоимость', max: 40 },
    ],
  },
]

export const LIST_LIMIT = 20

function hasRows(rows) {
  return (rows || []).some((row) => Object.values(row).some((value) => String(value || '').trim()))
}

export function profileItems(groups, lists, data) {
  const fields = groups.flatMap((group) => group.fields)
  return [
    ...fields.map((item) => ({ label: item.label, done: Boolean(String(data?.[item.key] || '').trim()) })),
    ...lists.map((list) => ({ label: list.title, done: hasRows(data?.[list.key]) })),
  ]
}
