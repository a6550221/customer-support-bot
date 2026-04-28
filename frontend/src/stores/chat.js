import { defineStore } from 'pinia'
import { ref } from 'vue'
import { chatApi } from '@/api'

export const useChatStore = defineStore('chat', () => {
  const sessions       = ref([])
  const activeSession  = ref(null)
  const chatMessages   = ref([])
  const loading        = ref(false)

  async function fetchSessions() {
    loading.value = true
    try {
      const res    = await chatApi.sessions()
      sessions.value = res.data
    } finally {
      loading.value = false
    }
  }

  async function acceptSession(id) {
    const res = await chatApi.accept(id)
    const idx = sessions.value.findIndex(s => s.id === id)
    if (idx !== -1) sessions.value[idx] = res.data
    if (activeSession.value?.id === id) activeSession.value = res.data
    return res.data
  }

  async function closeSession(id) {
    const res = await chatApi.close(id)
    const idx = sessions.value.findIndex(s => s.id === id)
    if (idx !== -1) sessions.value[idx] = res.data
    return res.data
  }

  async function loadMessages(sessionId) {
    const res      = await chatApi.getMessages(sessionId)
    chatMessages.value = res.data
  }

  async function sendMessage(sessionId, content) {
    const res = await chatApi.sendMessage(sessionId, content)
    // FIX: use onChatMessage for deduplication instead of direct push
    onChatMessage(res.data)
    return res.data
  }

  function onSessionUpdated(session) {
    const idx = sessions.value.findIndex(s => s.id === session.id)
    if (idx !== -1) sessions.value[idx] = session
    else sessions.value.unshift(session)
    if (activeSession.value?.id === session.id) activeSession.value = session
  }

  function onChatMessage(msg) {
    if (activeSession.value?.id === msg.session_id) {
      // FIX: deduplicate by message ID to prevent Pusher + direct-push duplicates
      if (!chatMessages.value.find(m => m.id === msg.id)) {
        chatMessages.value.push(msg)
      }
    }
    const session = sessions.value.find(s => s.id === msg.session_id)
    if (session) session.last_message = msg
  }

  return {
    sessions, activeSession, chatMessages, loading,
    fetchSessions, acceptSession, closeSession, loadMessages, sendMessage,
    onSessionUpdated, onChatMessage,
  }
})
