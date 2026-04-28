import axios from 'axios'
import { ElMessage } from 'element-plus'

const BACKEND = import.meta.env.VITE_API_URL
  || 'https://customer-support-bot-production-ec40.up.railway.app'

const api = axios.create({
  baseURL: `${BACKEND}/api/v1`,
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
})

api.interceptors.request.use(config => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

api.interceptors.response.use(
  res => res.data,
  err => {
    const msg = err.response?.data?.message || '請求失敗，請稍後再試'
    if (err.response?.status === 401) {
      // FIX: only redirect if not already on login page, and use location only
      // if no token is present (avoids race with router.push during logout)
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      if (window.location.pathname !== '/login') {
        window.location.href = '/login'
      }
    } else if (err.response?.status !== 422) {
      ElMessage.error(msg)
    }
    return Promise.reject(err.response?.data || err)
  }
)

// Auth
export const authApi = {
  login: data => api.post('/auth/login', data),
  logout: () => api.post('/auth/logout'),
  me: () => api.get('/auth/me'),
  updateStatus: status => api.put('/auth/status', { status }),
  updateProfile: data => api.put('/auth/profile', data),
}

// Tickets
export const ticketApi = {
  list: params => api.get('/tickets', { params }),
  get: id => api.get(`/tickets/${id}`),
  create: data => api.post('/tickets', data),
  update: (id, data) => api.put(`/tickets/${id}`, data),
  delete: id => api.delete(`/tickets/${id}`),
  assign: (id, agentId) => api.put(`/tickets/${id}/assign`, { agent_id: agentId }),
  updateStatus: (id, status) => api.put(`/tickets/${id}/status`, { status }),
  transfer: (id, agentId) => api.put(`/tickets/${id}/transfer`, { agent_id: agentId }),
}

// Messages
export const messageApi = {
  list: ticketId => api.get(`/tickets/${ticketId}/messages`),
  send: (ticketId, data) => {
    if (data instanceof FormData) {
      return api.post(`/tickets/${ticketId}/messages`, data, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
    }
    return api.post(`/tickets/${ticketId}/messages`, data)
  },
}

// Customers
export const customerApi = {
  list: params => api.get('/customers', { params }),
  get: id => api.get(`/customers/${id}`),
  create: data => api.post('/customers', data),
  update: (id, data) => api.put(`/customers/${id}`, data),
  delete: id => api.delete(`/customers/${id}`),
}

// Categories
export const categoryApi = {
  list: () => api.get('/categories'),
  create: data => api.post('/categories', data),
  update: (id, data) => api.put(`/categories/${id}`, data),
  delete: id => api.delete(`/categories/${id}`),
}

// Knowledge Base
export const knowledgeApi = {
  list: params => api.get('/knowledge', { params }),
  get: id => api.get(`/knowledge/${id}`),
  create: data => api.post('/knowledge', data),
  update: (id, data) => api.put(`/knowledge/${id}`, data),
  delete: id => api.delete(`/knowledge/${id}`),
  search: q => api.get('/knowledge/search', { params: { q } }),
}

// Quick Replies
export const quickReplyApi = {
  list: () => api.get('/quick-replies'),
  create: data => api.post('/quick-replies', data),
  update: (id, data) => api.put(`/quick-replies/${id}`, data),
  delete: id => api.delete(`/quick-replies/${id}`),
}

// Dashboard
export const dashboardApi = {
  stats: () => api.get('/dashboard/stats'),
  trend: days => api.get('/dashboard/trend', { params: { days } }),
  agents: () => api.get('/dashboard/agents'),
  csat: () => api.get('/dashboard/csat'),
}

// Users
export const userApi = {
  list: () => api.get('/users'),
  create: data => api.post('/users', data),
  update: (id, data) => api.put(`/users/${id}`, data),
  delete: id => api.delete(`/users/${id}`),
  updateRole: (id, role) => api.put(`/users/${id}/role`, { role }),
}

// Chat
export const chatApi = {
  sessions: () => api.get('/chat/sessions'),
  accept: id => api.put(`/chat/sessions/${id}/accept`),
  close: (id, createTicket = false) => api.put(`/chat/sessions/${id}/close`, { create_ticket: createTicket }),
  reopen: id => api.put(`/chat/sessions/${id}/reopen`),
  sendMessage: (id, content) => api.post(`/chat/sessions/${id}/messages`, { content }),
  getMessages: id => api.get(`/chat/sessions/${id}/messages`),
  typing: id => api.post(`/chat/sessions/${id}/typing`),
  updateVisitorInfo: (id, data) => api.put(`/chat/sessions/${id}/visitor-info`, data),
}

export default api
