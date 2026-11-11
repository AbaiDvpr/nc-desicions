export class ApiError extends Error {
  constructor(message, status) {
    super(message)
    this.status = status
  }
}

async function request(path, { method = 'GET', body } = {}) {
  const res = await fetch(`/api/admin${path}`, {
    method,
    credentials: 'include',
    headers: body ? { 'Content-Type': 'application/json' } : undefined,
    body: body ? JSON.stringify(body) : undefined,
  })
  if (res.status === 204) return null
  const data = await res.json().catch(() => null)
  if (!res.ok) {
    const detail = data?.detail
    const message = typeof detail === 'string'
      ? detail
      : Array.isArray(detail)
        ? detail.map((item) => item.msg).filter(Boolean).join(', ')
        : 'Что-то пошло не так'
    throw new ApiError(message || 'Что-то пошло не так', res.status)
  }
  return data
}

export const api = {
  me: () => request('/me'),
  login: (payload) => request('/login', { method: 'POST', body: payload }),
  logout: () => request('/logout', { method: 'POST' }),
  stats: () => request('/stats'),
  users: () => request('/users'),
  user: (id) => request(`/users/${id}`),
  adjustBalance: (id, payload) => request(`/users/${id}/balance`, { method: 'POST', body: payload }),
  assignTariff: (id, payload) => request(`/users/${id}/tariff`, { method: 'PUT', body: payload }),
  models: () => request('/models'),
  companies: () => request('/companies'),
  tariffs: () => request('/tariffs'),
  updateTariff: (id, payload) => request(`/tariffs/${id}`, { method: 'PUT', body: payload }),
  refills: () => request('/refills'),
  payRefill: (id) => request(`/refills/${id}/pay`, { method: 'POST' }),
  bp: () => request('/bp'),
  bpJobs: () => request('/bp/jobs'),
  updateBpSettings: (payload) => request('/bp/settings', { method: 'PUT', body: payload }),
  updateBpTemplate: (id, payload) => request(`/bp/templates/${id}`, { method: 'PUT', body: payload }),
  bpRequest: (payload) => request('/bp/request', { method: 'POST', body: payload }),
  femRates: () => request('/fem-rates'),
  updateFemRates: (payload) => request('/fem-rates', { method: 'PUT', body: payload }),
  govPrograms: () => request('/gov-programs'),
  refreshGovPrograms: () => request('/gov-programs/refresh', { method: 'POST' }),
  createGovProgram: (payload) => request('/gov-programs', { method: 'POST', body: payload }),
  updateGovProgram: (id, payload) => request(`/gov-programs/${id}`, { method: 'PUT', body: payload }),
}

export function formatMoney(value) {
  return `${new Intl.NumberFormat('ru-RU').format(value)} ₸`
}

export function formatDate(value) {
  return new Date(value).toLocaleString('ru-RU', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
