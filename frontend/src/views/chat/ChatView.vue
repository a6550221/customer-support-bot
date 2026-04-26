<template>
  <div class="chat-view">
    <!-- Session list -->
    <div class="session-list">
      <div class="list-header">
        <h3>即時對話</h3>
        <el-badge :value="waitingCount" :hidden="waitingCount === 0" type="danger">
          <el-button size="small" icon="Refresh" circle @click="chatStore.fetchSessions()" />
        </el-badge>
      </div>

      <div class="session-tabs">
        <el-radio-group v-model="tab" size="small">
          <el-radio-button value="">全部</el-radio-button>
          <el-radio-button value="waiting">等候中</el-radio-button>
          <el-radio-button value="active">進行中</el-radio-button>
        </el-radio-group>
      </div>

      <div class="sessions-scroll">
        <div
          v-for="s in filteredSessions"
          :key="s.id"
          :class="['session-item', s.status, activeId === s.id ? 'active' : '']"
          @click="selectSession(s)"
        >
          <div class="session-avatar">{{ s.visitor_name?.[0] || 'V' }}</div>
          <div class="session-info">
            <div class="session-name">{{ s.visitor_name || '訪客' }}</div>
            <div class="session-preview">{{ s.last_message?.content || s.source_url || '新對話' }}</div>
          </div>
          <div class="session-meta">
            <el-tag :type="statusTagType(s.status)" size="small">{{ statusLabel(s.status) }}</el-tag>
          </div>
        </div>
        <el-empty v-if="!filteredSessions.length" description="暫無對話" :image-size="60" />
      </div>
    </div>

    <!-- Chat window -->
    <div class="chat-window" v-if="chatStore.activeSession">
      <div class="chat-header">
        <div class="visitor-info">
          <el-avatar size="36">{{ chatStore.activeSession.visitor_name?.[0] || 'V' }}</el-avatar>
          <div>
            <div class="visitor-name">{{ chatStore.activeSession.visitor_name || '訪客' }}</div>
            <div class="visitor-source">{{ chatStore.activeSession.source_url || '—' }}</div>
          </div>
        </div>
        <div class="chat-actions">
          <el-button
            v-if="chatStore.activeSession.status === 'waiting'"
            type="success"
            size="small"
            @click="acceptSession"
          >
            接入對話
          </el-button>
          <el-button
            v-if="chatStore.activeSession.status === 'active'"
            type="danger"
            size="small"
            @click="closeSession"
          >
            結束對話
          </el-button>
        </div>
      </div>

      <div class="messages-area" ref="msgRef">
        <div v-for="msg in chatStore.chatMessages" :key="msg.id" :class="['chat-msg', msg.sender_type]">
          <div class="chat-bubble">
            <div class="bubble-meta">{{ senderLabel(msg.sender_type) }} · {{ formatTime(msg.created_at) }}</div>
            {{ msg.content }}
          </div>
        </div>
        <div v-if="isTyping" class="typing-indicator">對方正在輸入...</div>
      </div>

      <div class="chat-input" v-if="chatStore.activeSession.status === 'active'">
        <div class="input-row">
          <el-input
            v-model="msgInput"
            placeholder="輸入訊息..."
            @keyup.enter.exact="sendMsg"
            @input="emitTyping"
          />
          <el-button type="primary" icon="Position" @click="sendMsg">發送</el-button>
        </div>
        <div class="quick-actions">
          <el-button size="small" v-for="qr in quickReplies.slice(0, 3)" :key="qr.id" @click="msgInput = qr.content">
            {{ qr.title }}
          </el-button>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div class="chat-empty" v-else>
      <el-empty description="選擇一個對話開始" />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { useChatStore } from '@/stores/chat'
import { quickReplyApi } from '@/api'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import dayjs from 'dayjs'

const chatStore   = useChatStore()
const tab         = ref('')
const msgInput    = ref('')
const msgRef      = ref()
const quickReplies = ref([])
const isTyping    = ref(false)
let typingTimer   = null
let echo          = null

const activeId = computed(() => chatStore.activeSession?.id)

const filteredSessions = computed(() =>
  chatStore.sessions.filter(s => !tab.value || s.status === tab.value)
)
const waitingCount = computed(() => chatStore.sessions.filter(s => s.status === 'waiting').length)

function statusLabel(s) { return { waiting: '等候', active: '進行中', closed: '已結束' }[s] || s }
function statusTagType(s) { return { waiting: 'warning', active: 'success', closed: 'info' }[s] || '' }
function senderLabel(t) { return { visitor: '訪客', agent: '坐席', system: '系統' }[t] || t }
function formatTime(dt) { return dayjs(dt).format('HH:mm') }

async function selectSession(s) {
  chatStore.activeSession = s
  await chatStore.loadMessages(s.id)
  await nextTick()
  msgRef.value?.scrollTo({ top: msgRef.value.scrollHeight })

  if (echo) {
    echo.channel(`chat.${s.id}`)
      .listen('.chat.message', e => { chatStore.onChatMessage(e.message); scrollBottom() })
      .listen('.typing', () => {
        isTyping.value = true
        clearTimeout(typingTimer)
        typingTimer = setTimeout(() => { isTyping.value = false }, 3000)
      })
  }
}

async function acceptSession() {
  await chatStore.acceptSession(chatStore.activeSession.id)
}

async function closeSession() {
  await chatStore.closeSession(chatStore.activeSession.id)
}

async function sendMsg() {
  if (!msgInput.value.trim()) return
  await chatStore.sendMessage(chatStore.activeSession.id, msgInput.value)
  msgInput.value = ''
  scrollBottom()
}

function emitTyping() {
  // debounced typing indicator
}

function scrollBottom() {
  nextTick(() => { msgRef.value?.scrollTo({ top: msgRef.value.scrollHeight, behavior: 'smooth' }) })
}

watch(() => chatStore.chatMessages.length, scrollBottom)

onMounted(async () => {
  await chatStore.fetchSessions()
  const res = await quickReplyApi.list()
  quickReplies.value = res.data

  if (import.meta.env.VITE_PUSHER_APP_KEY) {
    window.Pusher = Pusher
    echo = new Echo({
      broadcaster: 'pusher',
      key: import.meta.env.VITE_PUSHER_APP_KEY,
      cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'ap3',
      forceTLS: true,
    })
    echo.channel('chat-sessions').listen('.session.updated', e => chatStore.onSessionUpdated(e.session))
  }
})
</script>

<style scoped>
.chat-view { display: flex; height: calc(100vh - 60px); overflow: hidden; }

.session-list { width: 280px; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; background: #fff; }
.list-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 16px 8px; }
.list-header h3 { font-size: 16px; font-weight: 600; }
.session-tabs { padding: 0 12px 12px; }
.sessions-scroll { flex: 1; overflow-y: auto; }

.session-item { display: flex; align-items: center; gap: 10px; padding: 12px 16px; cursor: pointer; border-bottom: 1px solid #f1f5f9; transition: background .15s; }
.session-item:hover { background: #f8fafc; }
.session-item.active { background: #eff6ff; border-left: 3px solid #4F46E5; }
.session-item.waiting { border-left: 3px solid #f97316; }

.session-avatar { width: 36px; height: 36px; border-radius: 50%; background: #4F46E5; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0; }
.session-info { flex: 1; min-width: 0; }
.session-name { font-weight: 600; font-size: 13px; }
.session-preview { font-size: 12px; color: #94a3b8; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.chat-window { flex: 1; display: flex; flex-direction: column; }
.chat-header { display: flex; justify-content: space-between; align-items: center; padding: 12px 20px; border-bottom: 1px solid #e2e8f0; background: #fff; }
.visitor-info { display: flex; align-items: center; gap: 10px; }
.visitor-name { font-weight: 600; }
.visitor-source { font-size: 12px; color: #94a3b8; }
.chat-actions { display: flex; gap: 8px; }

.messages-area { flex: 1; overflow-y: auto; padding: 16px 20px; display: flex; flex-direction: column; gap: 8px; }
.chat-msg { display: flex; }
.chat-msg.agent { justify-content: flex-end; }
.chat-bubble { max-width: 60%; background: #f1f5f9; border-radius: 12px; padding: 10px 14px; line-height: 1.5; }
.chat-msg.agent .chat-bubble { background: #4F46E5; color: #fff; }
.chat-msg.system .chat-bubble { background: #fef9c3; color: #92400e; font-size: 12px; text-align: center; }
.bubble-meta { font-size: 11px; color: #94a3b8; margin-bottom: 4px; }
.chat-msg.agent .bubble-meta { color: rgba(255,255,255,0.7); }
.typing-indicator { color: #94a3b8; font-size: 13px; font-style: italic; }

.chat-input { padding: 12px 20px; border-top: 1px solid #e2e8f0; background: #fff; }
.input-row { display: flex; gap: 8px; margin-bottom: 8px; }
.quick-actions { display: flex; gap: 6px; flex-wrap: wrap; }

.chat-empty { flex: 1; display: flex; align-items: center; justify-content: center; }
</style>
