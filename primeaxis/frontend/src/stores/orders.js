import { defineStore } from 'pinia'
import { ref } from 'vue'
import { orderApi } from '@/api'

export const useOrderStore = defineStore('orders', () => {
  const orders  = ref([])
  const current = ref(null)
  const loading = ref(false)
  const total   = ref(0)

  async function fetchOrders(params = {}) {
    loading.value = true
    try {
      const res = await orderApi.list(params)
      orders.value = res.data.data ?? res.data
      total.value  = res.data.total ?? res.data.length
    } finally { loading.value = false }
  }

  async function fetchOrder(id) {
    const res = await orderApi.get(id)
    current.value = res.data
    return res.data
  }

  async function createOrder(data) {
    const res = await orderApi.create(data)
    orders.value.unshift(res.data)
    return res.data
  }

  async function updateOrder(id, data) {
    const res = await orderApi.update(id, data)
    const idx = orders.value.findIndex(o => o.id === id)
    if (idx !== -1) orders.value[idx] = res.data
    if (current.value?.id === id) current.value = res.data
    return res.data
  }

  async function updateStatus(id, status) {
    const res = await orderApi.updateStatus(id, status)
    const idx = orders.value.findIndex(o => o.id === id)
    if (idx !== -1) orders.value[idx] = res.data
    if (current.value?.id === id) current.value = res.data
    return res.data
  }

  return { orders, current, loading, total, fetchOrders, fetchOrder, createOrder, updateOrder, updateStatus }
})
