export function formatMoney(value) {
  return `${new Intl.NumberFormat('ru-RU').format(value)} ₸`
}

export function formatSignedMoney(value) {
  const amount = new Intl.NumberFormat('ru-RU').format(Math.abs(value))
  if (value > 0) return `+${amount} ₸`
  if (value < 0) return `−${amount} ₸`
  return `${amount} ₸`
}

export function formatDate(value) {
  return new Date(value).toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
}
