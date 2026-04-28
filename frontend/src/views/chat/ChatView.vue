<template>
  <div class="chat-view">
    <!-- Session list -->
    <div class="session-list">
      <div class="list-header">
        <h3>即時對話</h3>
        <el-button
          size="small" icon="Refresh" circle
          :loading="chatStore.loading"
          @click="refreshSessions"
        />
      </div>

      <div class="session-tabs">
        <el-radio-group v-model="tab" size="small">
          <el-radio-button value="">全部</el-radio-button>
          <el-radio-button value="waiting">
            等候中
            <el-badge v-if="waitingCount > 0" :value="waitingCount" type="danger" class="tab-badge" />
          </el-radio-button>
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
          <div class="session-avatar">{{ s.visitor_name?.[0]?.toUpperCase() || 'V' }}</div>
          <div class="session-info">
            <div class="session-name">{{ s.visitor_name || '訪客' }}</div>
            <div class="session-preview">{{ s.last_message?.content || '新對話' }}</div>
          </div>
          <div class="session-meta">
            <el-tag :type="statusTagType(s.status)" size="small">{{ statusLabel(s.status) }}</el-tag>
            <!-- Show which agent is handling active session -->
            <div v-if="s.status === 'active' && s.agent" class="session-agent">
              {{ s.agent.name }}
            </div>
          </div>
        </div>
        <el-empty v-if="!filteredSessions.length" description="暫無對話" :image-size="60" />
      </div>
    </div>

    <!-- Chat window -->
    <div class="chat-window" v-if="chatStore.activeSession">
      <div class="chat-header">
        <div class="visitor-info">
          <el-avatar size="36">{{ chatStore.activeSession.visitor_name?.[0]?.toUpperCase() || 'V' }}</el-avatar>
          <div>
            <div class="visitor-name">{{ chatStore.activeSession.visitor_name || '訪客' }}</div>
            <div class="visitor-source">{{ chatStore.activeSession.source_url || '直接訪問' }}</div>
          </div>
        </div>
        <div class="chat-actions">
          <el-button
            v-if="chatStore.activeSession.status === 'waiting'"
            type="success" size="small"
            @click="acceptSession"
          >接入對話</el-button>
          <el-button
            v-if="chatStore.activeSession.status === 'active'"
            type="danger" size="small"
            @click="showCloseDialog = true"
          >結束對話</el-button>
          <el-button
            v-if="chatStore.activeSession.status === 'closed'"
            type="warning" size="small"
            @click="reopenSession"
          >重新開放</el-button>
          <!-- Toggle visitor info panel -->
          <el-button
            size="small" icon="UserFilled"
            :type="showInfoPanel ? 'primary' : ''"
            @click="showInfoPanel = !showInfoPanel"
          >訪客資訊</el-button>
        </div>
      </div>

      <div class="chat-body">
        <div class="messages-area" ref="msgRef">
          <div v-for="msg in chatStore.chatMessages" :key="msg.id" :class="['chat-msg', msg.sender_type]">
            <div class="chat-bubble">
              <div class="bubble-meta">
                <!-- Show actual agent name if available -->
                <span>{{ msg.sender_type === 'agent'
                  ? (msg.sender_name || '坐席')
                  : senderLabel(msg.sender_type) }}</span>
                <span>·</span>
                <span>{{ formatTime(msg.created_at) }}</span>
              </div>
              {{ msg.content }}
            </div>
          </div>
          <div v-if="isTyping" class="typing-indicator">對方正在輸入...</div>
        </div>

        <div class="chat-input" v-if="chatStore.activeSession.status === 'active'">
          <div class="input-row">
            <el-input
              v-model="msgInput"
              placeholder="輸入訊息... (Enter 發送，Shift+Enter 換行)"
              @keydown.enter.exact.prevent="sendMsg"
              @input="emitTyping"
            />
            <el-button type="primary" icon="Position" :loading="sending" @click="sendMsg">發送</el-button>
          </div>
          <div class="quick-actions">
            <el-button size="small" v-for="qr in quickReplies.slice(0, 3)" :key="qr.id" @click="msgInput = qr.content">
              {{ qr.title }}
            </el-button>
          </div>
        </div>

        <div v-if="chatStore.activeSession.status === 'closed'" class="closed-notice">
          <el-icon><InfoFilled /></el-icon>
          對話已結束。點擊「重新開放」可讓訪客重新排隊。
        </div>
      </div>

      <!-- Visitor info panel -->
      <transition name="slide-panel">
        <div class="info-panel" v-if="showInfoPanel">
          <div class="info-panel-header">訪客資訊</div>
          <div class="info-panel-body">
            <div class="info-field">
              <label>姓名</label>
              <el-input v-model="visitorForm.visitor_name" size="small" placeholder="訪客姓名" />
            </div>
            <div class="info-field">
              <label>電話</label>
              <el-input v-model="visitorForm.visitor_phone" size="small" placeholder="聯繫電話" />
            </div>
            <div class="info-field">
              <label>Email</label>
              <el-input v-model="visitorForm.visitor_email" size="small" placeholder="電子郵件" />
            </div>
            <div class="info-field">
              <label>備註</label>
              <el-input
                v-model="visitorForm.notes"
                type="textarea" :rows="4"
                placeholder="記錄訪客需求、聯繫意向等..."
                size="small"
              />
            </div>
            <el-button type="primary" size="small" style="width:100%" :loading="savingInfo" @click="saveVisitorInfo">
              儲存資訊
            </el-button>
            <div class="info-divider">對話資訊</div>
            <div class="info-readonly">
              <label>來源頁面</label>
              <span>{{ chatStore.activeSession.source_url || '—' }}</span>
            </div>
            <div class="info-readonly">
              <label>瀏覽器</label>
              <span>{{ chatStore.activeSession.browser?.split('/')[0] || '—' }}</span>
            </div>
            <div class="info-readonly">
              <label>負責坐席</label>
              <span>{{ chatStore.activeSession.agent?.name || '未接入' }}</span>
            </div>
            <div class="info-readonly">
              <label>開始時間</label>
              <span>{{ formatTime(chatStore.activeSession.created_at) }}</span>
            </div>
          </div>
        </div>
      </transition>
    </div>

    <!-- Empty state -->
    <div class="chat-empty" v-else>
      <el-empty description="選擇一個對話開始" />
    </div>

    <!-- Close confirmation dialog -->
    <el-dialog v-model="showCloseDialog" title="結束對話" width="360px" :close-on-click-modal="false">
      <p style="color:#475569;margin:0">請選擇結束方式：</p>
      <template #footer>
        <el-button @click="showCloseDialog = false">取消</el-button>
        <el-button type="primary" plain @click="doClose(false)" :loading="closing">僅結束對話</el-button>
        <el-button type="primary" @click="doClose(true)" :loading="closing">結束並建立工單</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { useChatStore } from '@/stores/chat'
import { quickReplyApi } from '@/api'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import dayjs from 'dayjs'

const chatStore    = useChatStore()
const tab          = ref('')
const msgInput     = ref('')
const msgRef       = ref()
const quickReplies = ref([])
const isTyping     = ref(false)
const sending      = ref(false)
const closing      = ref(false)
const savingInfo   = ref(false)
const showCloseDialog = ref(false)
const showInfoPanel   = ref(false)

// Visitor info form (bound to active session)
const visitorForm = ref({ visitor_name: '', visitor_email: '', visitor_phone: '', notes: '' })

let typingTimer   = null
let echo          = null
let activeChannel = null

const activeId = computed(() => chatStore.activeSession?.id)
const filteredSessions = computed(() =>
  chatStore.sessions.filter(s => !tab.value || s.status === tab.value)
)
const waitingCount = computed(() => chatStore.sessions.filter(s => s.status === 'waiting').length)

function statusLabel(s) { return { waiting: '等候', active: '進行中', closed: '已結束' }[s] || s }
function statusTagType(s) { return { waiting: 'warning', active: 'success', closed: 'info' }[s] || '' }
function senderLabel(t) { return { visitor: '訪客', agent: '坐席', system: '系統' }[t] || t }
function formatTime(dt) { return dayjs(dt).format('HH:mm') }

async function refreshSessions() {
  await chatStore.fetchSessions()
}

async function selectSession(s) {
  chatStore.activeSession = s
  // Sync visitor form with session data
  visitorForm.value = {
    visitor_name:  s.visitor_name  || '',
    visitor_email: s.visitor_email || '',
    visitor_phone: s.visitor_phone || '',
    notes:         s.notes         || '',
  }
  await chatStore.loadMessages(s.id)
  await nextTick()
  msgRef.value?.scrollTo({ top: msgRef.value.scrollHeight })

  if (echo) {
    if (activeChannel) {
      activeChannel.stopListening('.chat.message')
      activeChannel.stopListening('.typing')
    }
    activeChannel = echo.channel(`chat.${s.id}`)
    activeChannel
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
  ElMessage.success('已接入對話')
}

async function doClose(createTicket) {
  closing.value = true
  try {
    await chatStore.closeSession(chatStore.activeSession.id, createTicket)
    showCloseDialog.value = false
    ElMessage.success(createTicket ? '對話已結束，工單已建立' : '對話已結束')
  } finally {
    closing.value = false
  }
}

async function reopenSession() {
  await chatStore.reopenSession(chatStore.activeSession.id)
  ElMessage.success('對話已重新開放')
}

async function saveVisitorInfo() {
  savingInfo.value = true
  try {
    await chatStore.updateVisitorInfo(chatStore.activeSession.id, visitorForm.value)
    ElMessage.success('訪客資訊已儲存')
  } finally {
    savingInfo.value = false
  }
}

async function sendMsg() {
  if (sending.value || !msgInput.value.trim()) return
  sending.value = true
  try {
    await chatStore.sendMessage(chatStore.activeSession.id, msgInput.value)
    msgInput.value = ''
    scrollBottom()
  } finally {
    sending.value = false
  }
}

function emitTyping() { /* could add typing indicator broadcast here */ }

function scrollBottom() {
  nextTick(() => { msgRef.value?.scrollTo({ top: msgRef.value.scrollHeight, behavior: 'smooth' }) })
}

watch(() => chatStore.chatMessages.length, scrollBottom)

// When active session changes, sync form
watch(() => chatStore.activeSession, (s) => {
  if (s) {
    visitorForm.value = {
      visitor_name:  s.visitor_name  || '',
      visitor_email: s.visitor_email || '',
      visitor_phone: s.visitor_phone || '',
      notes:         s.notes         || '',
    }
  }
})

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

/* Session list */
.session-list { width: 280px; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; background: #fff; flex-shrink: 0; }
.list-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 16px 8px; }
.list-header h3 { font-size: 16px; font-weight: 600; }
.session-tabs { padding: 0 12px 12px; }
.tab-badge { margin-left: 4px; }
.sessions-scroll { flex: 1; overflow-y: auto; }

.session-item { display: flex; align-items: center; gap: 10px; padding: 12px 16px; cursor: pointer; border-bottom: 1px solid #f1f5f9; transition: background .15s; }
.session-item:hover { background: #f8fafc; }
.session-item.active { background: #eff6ff; border-left: 3px solid #4F46E5; }
.session-item.waiting { border-left: 3px solid #f97316; }
.session-item.closed { opacity: 0.6; }

.session-avatar { width: 36px; height: 36px; border-radius: 50%; background: #4F46E5; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0; }
.session-info { flex: 1; min-width: 0; }
.session-name { font-weight: 600; font-size: 13px; }
.session-preview { font-size: 12px; color: #94a3b8; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.session-meta { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; }
.session-agent { font-size: 11px; color: #64748b; }

/* Chat window */
.chat-window { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
.chat-header { display: flex; justify-content: space-between; align-items: center; padding: 12px 20px; border-bottom: 1px solid #e2e8f0; background: #fff; flex-shrink: 0; }
.visitor-info { display: flex; align-items: center; gap: 10px; }
.visitor-name { font-weight: 600; }
.visitor-source { font-size: 12px; color: #94a3b8; }
.chat-actions { display: flex; gap: 8px; }

.chat-body { flex: 1; display: flex; overflow: hidden; }

.messages-area { flex: 1; overflow-y: auto; padding: 16px 20px; display: flex; flex-direction: column; gap: 8px; }
.chat-msg { display: flex; }
.chat-msg.agent { justify-content: flex-end; }
.chat-msg.system { justify-content: center; }
.chat-bubble { max-width: 60%; background: #f1f5f9; border-radius: 12px; padding: 10px 14px; line-height: 1.5; }
.chat-msg.agent .chat-bubble { background: #4F46E5; color: #fff; }
.chat-msg.system .chat-bubble { background: #fef9c3; color: #92400e; font-size: 12px; max-width: 80%; text-align: center; }
.bubble-meta { display: flex; gap: 4px; font-size: 11px; color: #94a3b8; margin-bottom: 4px; }
.chat-msg.agent .bubble-meta { color: rgba(255,255,255,0.7); }
.typing-indicator { color: #94a3b8; font-size: 13px; font-style: italic; }

.chat-input { padding: 12px 20px; border-top: 1px solid #e2e8f0; background: #fff; flex-shrink: 0; }
.input-row { display: flex; gap: 8px; margin-bottom: 8px; }
.quick-actions { display: flex; gap: 6px; flex-wrap: wrap; }

.closed-notice {
  display: flex; align-items: center; gap: 8px; justify-content: center;
  padding: 12px 20px; background: #fef9c3; color: #92400e; font-size: 13px;
  flex-shrink: 0; border-top: 1px solid #e2e8f0;
}

/* Visitor info panel */
.info-panel {
  width: 260px; border-left: 1px solid #e2e8f0; display: flex;
  flex-direction: column; background: #fff; flex-shrink: 0; overflow: hidden;
}
.info-panel-header {
  padding: 12px 16px; font-weight: 600; font-size: 14px;
  border-bottom: 1px solid #f1f5f9; background: #f8fafc;
}
.info-panel-body { flex: 1; overflow-y: auto; padding: 12px 16px; display: flex; flex-direction: column; gap: 10px; }
.info-field { display: flex; flex-direction: column; gap: 4px; }
.info-field label { font-size: 12px; color: #94a3b8; font-weight: 500; }
.info-divider { font-size: 12px; color: #94a3b8; font-weight: 500; padding: 4px 0; border-top: 1px solid #f1f5f9; margin-top: 4px; }
.info-readonly { display: flex; justify-content: space-between; font-size: 12px; color: #374151; padding: 3px 0; }
.info-readonly label { color: #94a3b8; }
.info-readonly span { max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* Panel slide animation */
.slide-panel-enter-active, .slide-panel-leave-active { transition: width 0.25s ease, opacity 0.25s ease; }
.slide-panel-enter-from, .slide-panel-leave-to { width: 0; opacity: 0; }
.slide-panel-enter-to, .slide-panel-leave-from { width: 260px; opacity: 1; }

.chat-empty { flex: 1; display: flex; align-items: center; justify-content: center; }
</style>
