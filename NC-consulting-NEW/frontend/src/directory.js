export const LEGAL_FORMS = [
  { value: '1', label: 'ИП' },
  { value: '2', label: 'ТОО' },
]

export const TAX_REGIMES = [
  { value: '1', label: 'Общеустановленный порядок' },
  { value: '4', label: 'Упрощенная декларация' },
]

export const CURRENCIES = [
  { value: 'kzt', label: 'тенге' },
  { value: 'usd', label: 'долл.USD' },
  { value: 'eur', label: 'EUR' },
]

export function labelOf(options, value) {
  return options.find((item) => item.value === value)?.label || '—'
}
