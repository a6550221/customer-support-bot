import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('token') || '')
  const user  = ref(JSON.parse(localStorage.getItem('user') || 'null'))

  const isAdmin      = computed(() => user.value?.role === 'admin')
  const isSupervisor = computed(() => ['admin', 'supervisor'].includes(user.value?.role))

  async function login(email, password) {
    const res = await authApi.login({ email, password })
    token.value = res.data.token
    user.value  = res.data.user
    localStorage.setItem('token', res.data.token)
    localStorage.setItem('user', JSON.stringify(res.data.user))
  }

  async function logout() {
    try { await authApi.logout() } catch {}
    token.value = ''
    user.value  = null
    localStorage.removeItem('token')
    localStorage.removeItem('user')
  }

  async function fetchMe() {
    const res = await authApi.me()
    user.value = res.data
    localStorage.setItem('user', JSON.stringify(res.data))
  }

  async function setStatus(status) {
    await authApi.updateStatus(status)
    user.value = { ...user.value, status }
    localStorage.setItem('user', JSON.stringify(user.value))
  }

  return { token, user, isAdmin, isSupervisor, login, logout, fetchMe, setStatus }
})
