import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('pa_token') || '')
  const user  = ref(JSON.parse(localStorage.getItem('pa_user') || 'null'))

  const isLoggedIn = computed(() => !!token.value)

  async function login(credentials) {
    const res = await authApi.login(credentials)
    token.value = res.data.token
    user.value  = res.data.user
    localStorage.setItem('pa_token', token.value)
    localStorage.setItem('pa_user', JSON.stringify(user.value))
    return res.data
  }

  async function logout() {
    const old = token.value
    token.value = ''
    user.value  = null
    localStorage.removeItem('pa_token')
    localStorage.removeItem('pa_user')
    if (old) authApi.logout().catch(() => {})
  }

  async function setStatus(status) {
    await authApi.updateStatus(status)
    if (user.value) { user.value = { ...user.value, status } }
    localStorage.setItem('pa_user', JSON.stringify(user.value))
  }

  return { token, user, isLoggedIn, login, logout, setStatus }
})
