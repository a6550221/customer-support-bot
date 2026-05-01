import axios from 'axios'
import { ElMessage } from 'element-plus'

const BACKEND = import.meta.env.VITE_API_URL || 'https://primeaxis-backend-production.up.railway.app'

const api = axios.create({
  baseURL: `${BACKEND}/api/v1`,
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
})

api.interceptors.request.use(config => {
  const token = localStorage.getItem('pa_token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

api.interceptors.response.use(
  res => res.data,
  err => {
    const msg = err.response?.data?.message || '請求失敗，請稍後再試'
    if (err.response?.status === 401) {
      localStorage.removeItem('pa_token')
      localStorage.removeItem('pa_user')
      if (window.location.pathname !== '/login') window.location.href = '/login'
    } else if (err.response?.status !== 422) {
      ElMessage.error(msg)
    }
    return Promise.reject(err.response?.data || err)
  }
)

export const authApi = {
  login:  data   => api.post('/auth/login', data),
  logout: ()     => api.post('/auth/logout'),
  me:     ()     => api.get('/auth/me'),
  updateStatus: s => api.put('/auth/status', { status: s }),
}

export const orderApi = {
  list:   params => api.get('/orders', { params }),
  get:    id     => api.get(`/orders/${id}`),
  create: data   => api.post('/orders', data),
  update: (id, d) => api.put(`/orders/${id}`, d),
  delete: id     => api.delete(`/orders/${id}`),
  updateStatus: (id, status, note) => api.put(`/orders/${id}/status`, { status, note }),
  timeline: id   => api.get(`/orders/${id}/timeline`),
  addEvent: (id, text, type) => api.post(`/orders/${id}/events`, { text, type }),
}

export const dashboardApi = {
  stats:  () => api.get('/dashboard/stats'),
  trend:  days => api.get('/dashboard/trend', { params: { days } }),
  statusTrend: () => api.get('/dashboard/status-trend'),
  revenue: () => api.get('/dashboard/revenue'),
}

export const followupApi = {
  list:   date => api.get('/followup', { params: { date } }),
  update: (id, data) => api.put(`/followup/${id}`, data),
  create: data => api.post('/followup', data),
}

export const reportApi = {
  stats:    params => api.get('/reports/stats', { params }),
  agents:   params => api.get('/reports/agents', { params }),
  export:   params => api.get('/reports/export', { params, responseType: 'blob' }),
  autoSend: data   => api.post('/reports/auto-send', data),
  getAutoSend: () => api.get('/reports/auto-send'),
}

export const settingsApi = {
  getKnowledge:    ()         => api.get('/settings/knowledge'),
  createKnowledge: data       => api.post('/settings/knowledge', data),
  updateKnowledge: (id, data) => api.put(`/settings/knowledge/${id}`, data),
  deleteKnowledge: id         => api.delete(`/settings/knowledge/${id}`),
  getUsers:        ()         => api.get('/users'),
  createUser:      data       => api.post('/users', data),
  updateUser:      (id, d)    => api.put(`/users/${id}`, d),
  getSettings:     ()         => api.get('/settings'),
  saveSettings:    data       => api.put('/settings', data),
}

export const chatApi = {
  sessions:       ()           => api.get('/chat/sessions'),
  createSession:  data         => api.post('/chat/sessions', data),
  messages:       id           => api.get(`/chat/sessions/${id}/messages`),
  sendMessage:    (id, payload) => api.post(`/chat/sessions/${id}/messages`, payload),
}

export default api
