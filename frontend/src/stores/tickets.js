import { defineStore } from 'pinia'
import { ref } from 'vue'
import { ticketApi, messageApi } from '@/api'

export const useTicketStore = defineStore('tickets', () => {
  const tickets  = ref([])
  const current  = ref(null)
  const messages = ref([])
  const loading  = ref(false)
  const cursor   = ref(null)

  async function fetchTickets(params = {}) {
    loading.value = true
    try {
      const res    = await ticketApi.list(params)
      tickets.value = res.data.data
      cursor.value  = res.data.next_cursor
    } finally {
      loading.value = false
    }
  }

  async function fetchTicket(id) {
    loading.value = true
    try {
      const res    = await ticketApi.get(id)
      current.value = res.data
    } finally {
      loading.value = false
    }
  }

  async function fetchMessages(ticketId) {
    const res      = await messageApi.list(ticketId)
    messages.value = res.data
  }

  async function sendMessage(ticketId, data) {
    const res = await messageApi.send(ticketId, data)
    messages.value.push(res.data)
    return res.data
  }

  function addMessage(msg) {
    if (current.value && msg.ticket_id === current.value.id) {
      messages.value.push(msg)
    }
  }

  function updateTicketInList(updated) {
    const idx = tickets.value.findIndex(t => t.id === updated.id)
    if (idx !== -1) tickets.value[idx] = updated
    if (current.value?.id === updated.id) current.value = updated
  }

  return { tickets, current, messages, loading, cursor, fetchTickets, fetchTicket, fetchMessages, sendMessage, addMessage, updateTicketInList }
})
