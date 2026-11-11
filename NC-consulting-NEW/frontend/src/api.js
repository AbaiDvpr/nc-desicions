export class ApiError extends Error {
  constructor(message, status) {
    super(message)
    this.status = status
  }
}

function errorMessage(data, fallback) {
  if (!data?.detail) return fallback
  if (typeof data.detail === 'string') return data.detail
  if (Array.isArray(data.detail)) {
    return data.detail.map((d) => d.msg).join(', ')
  }
  return fallback
}

async function request(path, { method = 'GET', body, form } = {}) {
  const res = await fetch(`/api${path}`, {
    method,
    credentials: 'include',
    headers: form || !body ? undefined : { 'Content-Type': 'application/json' },
    body: form ?? (body ? JSON.stringify(body) : undefined),
  })

  if (res.status === 204) return null

  const data = await res.json().catch(() => null)
  if (!res.ok) {
    throw new ApiError(errorMessage(data, 'Что-то пошло не так'), res.status)
  }
  return data
}

export const api = {
  me: () => request('/auth/me'),
  login: (payload) => request('/auth/login', { method: 'POST', body: payload }),
  register: (payload) => request('/auth/register', { method: 'POST', body: payload }),
  logout: () => request('/auth/logout', { method: 'POST' }),
  updateProfile: (payload) => request('/profile', { method: 'PUT', body: payload }),
  changePassword: (payload) => request('/profile/password', { method: 'PUT', body: payload }),
  uploadAvatar: (file) => {
    const form = new FormData()
    form.append('file', file, 'avatar.jpg')
    return request('/profile/avatar', { method: 'POST', form })
  },
  deleteAvatar: () => request('/profile/avatar', { method: 'DELETE' }),
  listTariffs: (period) => request(`/tariffs?period=${period}`),
  currentTariff: () => request('/tariffs/current'),
  getTariff: (id) => request(`/tariffs/${id}`),
  buyTariff: (id, payload) => request(`/tariffs/${id}/buy`, { method: 'POST', body: payload }),
  payTariffWooppay: (id, payload) => request(`/payments/tariffs/${id}/wooppay`, { method: 'POST', body: payload }),
  topupWooppay: (payload) => request('/payments/topup/wooppay', { method: 'POST', body: payload }),
  topupIoka: (payload) => request('/payments/topup/ioka', { method: 'POST', body: payload }),
  requestRefill: (payload) => request('/payments/topup/request', { method: 'POST', body: payload }),
  syncInvoice: (id) => request(`/payments/invoices/${id}/sync`, { method: 'POST' }),
  listCompanies: () => request('/companies'),
  createCompany: (payload) => request('/companies', { method: 'POST', body: payload }),
  getCompany: (id) => request(`/companies/${id}`),
  updateCompany: (id, payload) => request(`/companies/${id}`, { method: 'PUT', body: payload }),
  listRequisites: (id) => request(`/companies/${id}/requisites`),
  createRequisite: (id, payload) => request(`/companies/${id}/requisites`, { method: 'POST', body: payload }),
  updateRequisite: (id, requisiteId, payload) =>
    request(`/companies/${id}/requisites/${requisiteId}`, { method: 'PUT', body: payload }),
  deleteRequisite: (id, requisiteId) =>
    request(`/companies/${id}/requisites/${requisiteId}`, { method: 'DELETE' }),
  listBanks: () => request('/banks'),
  govPrograms: () => request('/gov-programs'),
  nbkBaseRate: () => request('/gov-programs/base-rate'),
  femRates: () => request('/fem-rates'),
  listCounterparties: () => request('/counterparties'),
  getCounterparty: (id) => request(`/counterparties/${id}`),
  createCounterparty: (payload) => request('/counterparties', { method: 'POST', body: payload }),
  updateCounterparty: (id, payload) => request(`/counterparties/${id}`, { method: 'PUT', body: payload }),
  deleteCounterparty: (id) => request(`/counterparties/${id}`, { method: 'DELETE' }),
  listFinModels: ({ archived = false, page = 1, q = '' } = {}) => {
    const params = new URLSearchParams()
    if (archived) params.set('archived', 'true')
    if (page > 1) params.set('page', String(page))
    if (q) params.set('q', q)
    const query = params.toString()
    return request(`/fin-models${query ? `?${query}` : ''}`)
  },
  archiveFinModel: (id) => request(`/fin-models/${id}/archive`, { method: 'POST' }),
  restoreFinModel: (id) => request(`/fin-models/${id}/restore`, { method: 'POST' }),
  getFinModel: (id) => request(`/fin-models/${id}`),
  updateFinModel: (id, payload) => request(`/fin-models/${id}`, { method: 'PUT', body: payload }),
  createFinModel: (payload, draftId) =>
    request(`/fin-models${draftId ? `?draft_id=${draftId}` : ''}`, { method: 'POST', body: payload }),
  saveDraft: (payload) => request('/fin-models/drafts', { method: 'POST', body: payload }),
  updateDraft: (id, payload) => request(`/fin-models/drafts/${id}`, { method: 'PUT', body: payload }),
  getDraft: (id) => request(`/fin-models/drafts/${id}`),
  bpCatalog: () => request('/bp'),
  bpJob: (id) => request(`/fin-models/${id}/business-plan`),
  startBpJob: (id, { templateId, force = false } = {}) => {
    const params = new URLSearchParams()
    if (templateId) params.set('template_id', String(templateId))
    if (force) params.set('force', 'true')
    const query = params.toString()
    return request(`/fin-models/${id}/business-plan${query ? `?${query}` : ''}`, { method: 'POST' })
  },
  retryBpJob: (id, jobId) => request(`/fin-models/${id}/business-plan/${jobId}/retry`, { method: 'POST' }),
  reviewBpTask: (id, jobId, taskId, answer) =>
    request(`/fin-models/${id}/business-plan/${jobId}/tasks/${taskId}/review`, { method: 'POST', body: { answer } }),
}

export const finModelFiles = (id) => ({
  word: `/api/fin-models/${id}/report.docx`,
  pdf: `/api/fin-models/${id}/report.pdf`,
  excel: `/api/fin-models/${id}/fem-v5.xlsx`,
})
