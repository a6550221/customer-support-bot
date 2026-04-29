<template>
  <div class="console-view">
    <!-- Tab bar -->
    <div class="console-tabs">
      <div v-for="t in tabs" :key="t.id" :class="['tab', activeTab === t.id && 'tab-active']" @click="activeTab = t.id">
        <span>{{ t.icon }}</span> {{ t.label }}
        <span v-if="t.badge && activeTab !== t.id" class="tab-badge">{{ t.badge }}</span>
      </div>
    </div>

    <!-- Voice + Text tab -->
    <div v-if="activeTab === 'voice'" class="voice-tab">
      <!-- Left: Voice Panel -->
      <div class="voice-panel">
        <div class="voice-panel-header">
          <span class="panel-label">語音通話 Voice Call</span>
        </div>

        <!-- Caller Card -->
        <div class="caller-card" :class="callState">
          <div class="caller-avatar-wrap">
            <div class="caller-avatar">{{ linkedOrder.customer[0] }}</div>
            <div v-if="callState === 'ringing'" class="ring-anim" />
          </div>
          <div class="caller-name">{{ linkedOrder.customer }}</div>
          <div class="caller-phone">{{ linkedOrder.phone }}</div>
          <div class="call-status-label" :class="callState">
            {{ callStateLabel }}
            <span v-if="callState === 'active'" class="call-timer">{{ fmtDuration(callDuration) }}</span>
          </div>
        </div>

        <!-- Waveform -->
        <div class="waveform">
          <div
            v-for="(h, i) in waveHeights"
            :key="i"
            class="wave-bar"
            :style="{ height: h + 'px', animationDelay: (i * 0.05) + 's' }"
            :class="{ active: callState === 'active' }"
          />
        </div>

        <!-- Call Controls -->
        <div class="call-controls">
          <template v-if="callState === 'idle'">
            <button class="call-btn simulate" @click="simulateRinging">模擬來電</button>
          </template>
          <template v-else-if="callState === 'ringing'">
            <button class="call-btn answer" @click="answerCall">📞 接聽</button>
            <button class="call-btn hangup" @click="endCall">📵 拒接</button>
          </template>
          <template v-else-if="callState === 'active'">
            <button class="call-btn mute" :class="{ on: muted }" @click="muted = !muted">🔇</button>
            <button class="call-btn hold" :class="{ on: onHold }" @click="onHold = !onHold">⏸</button>
            <button class="call-btn hangup" @click="endCall">📵 掛斷</button>
          </template>
        </div>

        <!-- Quick Notes -->
        <div class="quick-notes">
          <div class="panel-label" style="margin-bottom:8px">快捷備注 Quick Notes</div>
          <div class="notes-list">
            <div v-for="n in quickNotes" :key="n" class="note-tag" @click="appendToChat(n)">+ {{ n }}</div>
          </div>
        </div>
      </div>

      <!-- Center: Chat -->
      <div class="chat-panel">
        <div class="chat-messages" ref="msgRef">
          <div v-for="msg in messages" :key="msg.id" :class="['msg-row', msg.from]">
            <div v-if="msg.from === 'axi'" class="axi-label">
              <span class="axi-dot">✦</span> Axi
            </div>
            <div :class="['msg-bubble', msg.from]">
              {{ msg.text }}
            </div>
            <div class="msg-time">{{ msg.time }}</div>
          </div>
          <div v-if="axiThinking" class="msg-row axi">
            <div class="axi-label"><span class="axi-dot">✦</span> Axi</div>
            <div class="msg-bubble axi thinking">思考中 ···</div>
          </div>
        </div>

        <div class="chat-input-area">
          <div class="input-row">
            <textarea
              v-model="inputText"
              class="chat-input"
              placeholder="輸入訊息... Enter 發送"
              rows="2"
              @keydown.enter.exact.prevent="sendMessage"
            />
            <button class="send-btn" @click="sendMessage">↑</button>
          </div>
          <div class="input-actions">
            <button class="axi-btn" @click="askAxi" :disabled="axiThinking">✦ 詢問 Axi</button>
          </div>
        </div>
      </div>

      <!-- Right: Order Info -->
      <div class="info-panel">
        <div class="info-tabs">
          <button :class="['info-tab', infoTab === 'order' && 'active']" @click="infoTab = 'order'">訂單資訊</button>
          <button :class="['info-tab', infoTab === 'customer' && 'active']" @click="infoTab = 'customer'">客戶資訊</button>
        </div>
        <div class="info-body">
          <div class="auto-link-tag">✦ {{ linkedOrder.id }} 自動關聯</div>

          <template v-if="infoTab === 'order'">
            <InfoField label="訂單號" :value="linkedOrder.id" mono />
            <InfoField label="客戶" :value="linkedOrder.customer" />
            <InfoField label="路線" :value="linkedOrder.route" />
            <InfoField label="狀態" :value="linkedOrder.status" :status="linkedOrder.status_key" />
            <InfoField label="重量" :value="linkedOrder.weight" />
            <div class="info-field">
              <div class="info-label">收件地址</div>
              <input v-model="linkedOrder.address" class="info-input" />
            </div>
            <div class="info-field">
              <div class="info-label">備注</div>
              <textarea v-model="linkedOrder.notes" class="info-input info-textarea" rows="3" />
            </div>
            <button class="save-btn" @click="saveOrderInfo">儲存 Save</button>
          </template>

          <template v-else>
            <InfoField label="姓名" :value="linkedOrder.customer" />
            <InfoField label="電話" :value="linkedOrder.phone" />
            <InfoField label="郵件" value="mei.zhang@example.com" />
            <div class="info-section-title">通話歷史</div>
            <div v-for="h in callHistory" :key="h.id" class="history-item">
              <span class="hist-icon">{{ h.type === 'call' ? '📞' : '💬' }}</span>
              <div>
                <div class="hist-text">{{ h.text }}</div>
                <div class="hist-time">{{ h.time }}</div>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>

    <!-- Email tab -->
    <EmailInbox v-if="activeTab === 'email'" />
  </div>
</template>

<script setup>
import { ref, nextTick, onUnmounted } from 'vue'
import { ElMessage } from 'element-plus'
import EmailInbox from './EmailInbox.vue'
import InfoField from './InfoField.vue'

const activeTab = ref('voice')
const tabs = [
  { id: 'voice', label: '語音 + 文字', icon: '◎' },
  { id: 'email', label: '郵件中心',     icon: '✉', badge: 2 },
]

// ── Voice state ──
const callState   = ref('idle')   // idle | ringing | active | ended
const callDuration = ref(0)
const muted   = ref(false)
const onHold  = ref(false)
const waveHeights = ref(Array(32).fill(4))
let timerInterval = null, waveInterval = null

const callStateLabel = { idle: '等待來電', ringing: '來電中...', active: '通話中', ended: '通話結束' }

function fmtDuration(s) {
  return `${String(Math.floor(s/60)).padStart(2,'0')}:${String(s%60).padStart(2,'0')}`
}

function simulateRinging() {
  callState.value = 'ringing'
  waveHeights.value = Array(32).fill(4)
}

function answerCall() {
  callState.value = 'active'
  callDuration.value = 0
  timerInterval = setInterval(() => callDuration.value++, 1000)
  waveInterval  = setInterval(() => {
    waveHeights.value = Array(32).fill(0).map(() => 4 + Math.random() * 44)
  }, 120)
}

function endCall() {
  callState.value = 'ended'
  clearInterval(timerInterval)
  clearInterval(waveInterval)
  waveHeights.value = Array(32).fill(4)
  setTimeout(() => { callState.value = 'idle'; callDuration.value = 0 }, 2000)
}

onUnmounted(() => { clearInterval(timerInterval); clearInterval(waveInterval) })

// ── Chat ──
const msgRef = ref()
const inputText  = ref('')
const axiThinking = ref(false)
const infoTab = ref('order')

const messages = ref([
  { id: 1, from: 'axi',      text: '您好！我是 Axi，PrimeAxis 智能助理。請問有什麼可以幫您？\nHello! I\'m Axi. How may I help?', time: '10:28' },
  { id: 2, from: 'customer', text: '你好，我想查一下我的貨物到哪裡了，訂單號是 PA-2024-0890', time: '10:29' },
  { id: 3, from: 'axi',      text: '查詢結果：訂單 PA-2024-0890 狀態為「異常」，派送地址資訊不完整，需要客服跟進。', time: '10:29' },
  { id: 4, from: 'agent',    text: '您好，我是客服陳小明。我看到您的訂單有地址問題需要確認。', time: '10:30' },
  { id: 5, from: 'customer', text: '是的，地址應該是九龍灣宏開道8號，請幫我更新', time: '10:31' },
])

const axiResponses = [
  '根據系統資料，訂單 PA-2024-0890 已於昨日 18:32 抵達北京中轉站。建議更新地址後安排明日重新派送。',
  '客戶 PA-2024-0890 歷史記錄：2024年共12單，整體滿意度評分 4.2/5，曾反映過一次延誤問題。',
  '根據目前物流資料，HK→BJ 路線平均派送時間為 2-3 個工作日。目前有 3 單在此路線運輸中。',
  '建議回覆內容：感謝您的等候，我們已為您更新地址至九龍灣宏開道8號，預計明日上午重新安排派送，請保持電話暢通。',
]
let axiIdx = 0

function sendMessage() {
  if (!inputText.value.trim()) return
  messages.value.push({
    id: Date.now(), from: 'agent',
    text: inputText.value,
    time: new Date().toLocaleTimeString('zh-HK', { hour: '2-digit', minute: '2-digit' }),
  })
  inputText.value = ''
  scrollBottom()
}

function appendToChat(note) { inputText.value = note }

function askAxi() {
  axiThinking.value = true
  setTimeout(() => {
    axiThinking.value = false
    messages.value.push({
      id: Date.now(), from: 'axi',
      text: axiResponses[axiIdx % axiResponses.length],
      time: new Date().toLocaleTimeString('zh-HK', { hour: '2-digit', minute: '2-digit' }),
    })
    axiIdx++
    scrollBottom()
  }, 1600)
}

function scrollBottom() {
  nextTick(() => { if (msgRef.value) msgRef.value.scrollTop = msgRef.value.scrollHeight })
}

function saveOrderInfo() { ElMessage.success('訂單資訊已儲存') }

// ── Order / Customer data ──
const linkedOrder = ref({
  id: 'PA-2024-0890', customer: '張美玲', phone: '+852 9123 4567',
  route: 'HK → BJ', status: '異常', status_key: 'exception',
  weight: '12.5 kg', address: '九龍灣宏開道8號', notes: '地址待確認',
})

const callHistory = [
  { id:1, type:'call',  text:'通話 08:32 (2分15秒)', time:'今日 10:30' },
  { id:2, type:'chat',  text:'文字對話 - 訂單查詢',  time:'2024-11-25' },
  { id:3, type:'call',  text:'通話 未接聽',           time:'2024-11-24' },
]

const quickNotes = ['地址已確認', '客戶已告知延誤', '安排重新派送', '需要上門取件', '轉交主管處理']
</script>

<style scoped>
.console-view { display: flex; flex-direction: column; height: 100%; overflow: hidden; }

/* Tabs */
.console-tabs {
  display: flex; gap: 0; background: #fff;
  border-bottom: 1px solid var(--pa-border); flex-shrink: 0; padding: 0 24px;
}
.tab {
  padding: 12px 16px; cursor: pointer; font-size: 13px; color: #706560;
  border-bottom: 2.5px solid transparent;
  display: flex; align-items: center; gap: 6px; transition: all 0.14s;
}
.tab:hover { color: var(--pa-orange); }
.tab-active { color: var(--pa-orange); font-weight: 700; border-bottom-color: var(--pa-orange); }
.tab-badge {
  min-width: 16px; height: 16px; border-radius: 50%;
  background: var(--pa-danger); color: #fff; font-size: 9px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
}

/* Voice tab layout */
.voice-tab { flex: 1; display: grid; grid-template-columns: 260px 1fr 260px; overflow: hidden; }

/* ── Voice Panel ── */
.voice-panel {
  background: #fff; border-right: 1px solid var(--pa-border);
  display: flex; flex-direction: column; overflow: hidden;
}
.voice-panel-header { padding: 14px 16px 0; }
.panel-label { font-size: 11px; font-weight: 700; color: #9e9890; text-transform: uppercase; letter-spacing: 0.5px; }

.caller-card {
  margin: 12px; padding: 16px 12px;
  border-radius: 12px; background: #faf8f5;
  display: flex; flex-direction: column; align-items: center; gap: 6px;
  text-align: center; transition: background 0.2s;
}
.caller-card.active { background: #fff3e6; }
.caller-avatar-wrap { position: relative; margin-bottom: 4px; }
.caller-avatar {
  width: 52px; height: 52px; border-radius: 50%;
  background: var(--pa-orange); color: #fff;
  font-size: 22px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
}
.ring-anim {
  position: absolute; inset: -6px; border-radius: 50%;
  border: 2px solid var(--pa-orange); opacity: 0.5;
  animation: ring 1s ease infinite;
}
.caller-name  { font-size: 15px; font-weight: 700; color: #2c2520; }
.caller-phone { font-size: 12px; color: #9e9890; }
.call-status-label {
  font-size: 11px; font-weight: 600; color: #9e9890;
  display: flex; align-items: center; gap: 6px;
}
.call-status-label.ringing { color: var(--pa-orange); animation: blink 1s infinite; }
.call-status-label.active  { color: #2a9d5c; }
.call-timer { font-family: monospace; font-size: 13px; font-weight: 700; }

/* Waveform */
.waveform {
  display: flex; align-items: center; justify-content: center;
  gap: 3px; height: 60px; padding: 0 16px;
}
.wave-bar {
  width: 3px; border-radius: 3px; background: #e0dbd5;
  transition: height 0.12s ease;
}
.wave-bar.active { background: var(--pa-orange); }

/* Call Controls */
.call-controls {
  display: flex; gap: 10px; justify-content: center;
  padding: 8px 16px 12px; border-top: 1px solid var(--pa-border);
}
.call-btn {
  height: 40px; border-radius: 20px; border: none; cursor: pointer;
  font-size: 13px; font-weight: 600; padding: 0 16px;
  transition: all 0.15s;
}
.call-btn.simulate { background: #f0ede8; color: #706560; }
.call-btn.simulate:hover { background: #e5e0d8; }
.call-btn.answer { background: #2a9d5c; color: #fff; }
.call-btn.answer:hover { background: #228a4e; }
.call-btn.hangup { background: #d44e2a; color: #fff; }
.call-btn.hangup:hover { background: #bf4224; }
.call-btn.mute, .call-btn.hold {
  width: 40px; padding: 0; border-radius: 50%;
  background: #f0ede8; color: #706560;
}
.call-btn.mute.on, .call-btn.hold.on { background: var(--pa-orange-light); color: var(--pa-orange); }

/* Quick Notes */
.quick-notes { flex: 1; padding: 12px 16px; overflow: hidden; }
.notes-list { display: flex; flex-direction: column; gap: 5px; }
.note-tag {
  padding: 6px 10px; background: #faf8f5; border: 1px solid var(--pa-border);
  border-radius: 7px; font-size: 11px; color: #706560; cursor: pointer;
  transition: all 0.12s;
}
.note-tag:hover { background: var(--pa-orange-light); border-color: var(--pa-orange-border); color: var(--pa-orange); }

/* ── Chat Panel ── */
.chat-panel {
  display: flex; flex-direction: column; overflow: hidden;
  background: var(--pa-bg);
}
.chat-messages {
  flex: 1; overflow-y: auto; padding: 16px;
  display: flex; flex-direction: column; gap: 12px;
}
.msg-row { display: flex; flex-direction: column; }
.msg-row.agent   { align-items: flex-end; }
.msg-row.axi     { align-items: flex-start; }
.msg-row.customer{ align-items: flex-start; }

.axi-label {
  display: flex; align-items: center; gap: 4px;
  font-size: 11px; font-weight: 700; color: var(--pa-orange); margin-bottom: 3px;
}
.axi-dot {
  width: 14px; height: 14px; border-radius: 50%;
  background: var(--pa-orange); color: #fff;
  font-size: 8px; display: flex; align-items: center; justify-content: center;
}

.msg-bubble {
  max-width: 78%; padding: 10px 14px; border-radius: 12px;
  font-size: 13px; line-height: 1.5; white-space: pre-wrap;
}
.msg-bubble.customer { background: #f5f2ee; border-radius: 12px 12px 12px 3px; }
.msg-bubble.agent    { background: var(--pa-orange); color: #fff; border-radius: 12px 12px 3px 12px; }
.msg-bubble.axi      { background: linear-gradient(135deg, #fdf5ec, #f0faf5); border: 1px solid #f0e8d8; border-radius: 12px 12px 12px 3px; }
.msg-bubble.thinking { color: #9e9890; font-style: italic; }
.msg-time { font-size: 10px; color: #9e9890; margin-top: 3px; padding: 0 2px; }

.chat-input-area {
  padding: 12px 16px; border-top: 1px solid var(--pa-border); background: #fff; flex-shrink: 0;
}
.input-row { display: flex; gap: 8px; margin-bottom: 8px; }
.chat-input {
  flex: 1; border: 1.5px solid var(--pa-border); border-radius: 10px;
  padding: 8px 12px; font-size: 13px; font-family: inherit; resize: none;
  outline: none; background: #faf8f5; color: #2c2520; line-height: 1.4;
}
.chat-input:focus { border-color: var(--pa-orange); box-shadow: 0 0 0 3px rgba(232,133,26,0.1); }
.send-btn {
  width: 36px; height: 36px; border-radius: 9px;
  background: var(--pa-orange); border: none; color: #fff;
  font-size: 18px; cursor: pointer; display: flex; align-items: center; justify-content: center;
  align-self: flex-end;
}
.send-btn:hover { background: #d4760f; }
.input-actions { display: flex; gap: 8px; }
.axi-btn {
  padding: 5px 14px; border-radius: 8px;
  background: linear-gradient(135deg, #fdf5ec, #f0faf5);
  border: 1.5px solid #f0e8d8; color: #b36a10;
  font-size: 12px; font-weight: 700; cursor: pointer;
}
.axi-btn:hover { background: var(--pa-orange-light); }
.axi-btn:disabled { opacity: 0.5; cursor: not-allowed; }

/* ── Info Panel ── */
.info-panel {
  background: #fff; border-left: 1px solid var(--pa-border);
  display: flex; flex-direction: column; overflow: hidden;
}
.info-tabs {
  display: flex; gap: 4px; padding: 12px 14px;
  border-bottom: 1px solid var(--pa-border);
}
.info-tab {
  padding: 5px 10px; border-radius: 7px; border: none; cursor: pointer;
  font-size: 12px; font-weight: 400; color: #706560; background: transparent;
  font-family: inherit;
}
.info-tab.active { background: var(--pa-orange-light); color: var(--pa-orange); font-weight: 600; }
.info-body { flex: 1; padding: 14px; overflow-y: auto; }
.auto-link-tag {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 3px 10px; border-radius: 20px;
  background: var(--pa-orange-light); color: var(--pa-orange);
  font-size: 10px; font-weight: 700; margin-bottom: 12px;
}
.info-field { margin-bottom: 10px; }
.info-label { font-size: 10px; color: #9e9890; margin-bottom: 3px; }
.info-input {
  width: 100%; border: 1.5px solid var(--pa-border); border-radius: 7px;
  padding: 6px 9px; font-size: 12px; font-family: inherit;
  color: #2c2520; background: #faf8f5; outline: none;
}
.info-input:focus { border-color: var(--pa-orange); }
.info-textarea { resize: vertical; min-height: 64px; }
.save-btn {
  width: 100%; padding: 8px; border-radius: 8px;
  background: var(--pa-orange); border: none; color: #fff;
  font-size: 13px; font-weight: 700; cursor: pointer; margin-top: 4px;
}
.save-btn:hover { background: #d4760f; }
.info-section-title {
  font-size: 10px; font-weight: 700; color: #9e9890; text-transform: uppercase;
  letter-spacing: 0.5px; margin: 14px 0 8px;
}
.history-item {
  display: flex; gap: 8px; align-items: flex-start;
  padding: 8px 0; border-bottom: 1px solid #faf8f5;
}
.hist-icon { font-size: 14px; flex-shrink: 0; margin-top: 1px; }
.hist-text { font-size: 12px; color: #2c2520; }
.hist-time { font-size: 10px; color: #9e9890; margin-top: 2px; }

@keyframes ring {
  0%,100% { transform: scale(1); opacity: 0.5; }
  50%      { transform: scale(1.35); opacity: 0; }
}
@keyframes blink {
  0%,100% { opacity: 1; }
  50%     { opacity: 0.3; }
}
</style>
