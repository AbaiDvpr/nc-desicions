export const LEGAL_FORMS = [
  { value: '1', label: 'ИП' },
  { value: '2', label: 'ТОО' },
]

export const TAX_REGIMES = [
  { value: '1', label: 'Общеустановленный порядок' },
  { value: '4', label: 'Упрощенная декларация' },
]

export const CURRENCIES = [
  { value: 'kzt', label: '₸ Тенге (KZT)' },
  { value: 'usd', label: '$ Доллар (USD)' },
  { value: 'eur', label: '€ Евро (EUR)' },
  { value: 'rub', label: '₽ Рубль (RUB)' },
  { value: 'uzs', label: 'сум Узбекский сум (UZS)' },
  { value: 'kgs', label: 'сом Кыргызский сом (KGS)' },
  { value: 'tjs', label: 'смн Таджикский сомони (TJS)' },
  { value: 'aed', label: 'Dh Дирхам ОАЭ (AED)' },
]

export function labelOf(options, value) {
  return options.find((item) => item.value === value)?.label || '—'
}
