<template>
  <div class="ticket-detail" v-loading="ticketStore.loading">
    <template v-if="ticketStore.current">
      <!-- Header bar -->
      <div class="detail-header">
        <div class="header-left">
          <el-button icon="ArrowLeft" @click="$router.back()" />
          <div>
            <span class="ticket-no">{{ ticketStore.current.ticket_no }}</span>
            <h3 class="ticket-subject">{{ ticketStore.current.subject }}</h3>
          </div>
        </div>
        <div class="header-actions">
          <el-select :model-value="ticketStore.current.status" @change="changeStatus" size="small" style="width: 120px">
            <el-option label="待處理" value="open" />
            <el-option label="跟進中" value="pending" />
            <el-option label="已解決" value="resolved" />
            <el-option label="已關閉" value="closed" />
          </el-select>
          <el-select :model-value="ticketStore.current.priority" @change="changePriority" size="small" style="width: 100px">
            <el-option label="緊急" value="urgent" />
            <el-option label="高" value="high" />
            <el-option label="中" value="medium" />
            <el-option label="低" value="low" />
          </el-select>
          <el-button size="small" @click="showTransfer = true">轉接</el-button>
        </div>
      </div>

      <div class="detail-body">
        <!-- Message thread -->
        <div class="messages-panel">
          <div class="messages-scroll" ref="scrollRef">
            <div v-for="msg in ticketStore.messages" :key="msg.id" :class="['msg-item', msg.sender_type, msg.is_internal ? 'internal' : '']">
              <div class="msg-meta">
                <span class="msg-sender">{{ msg.sender_type === 'agent' ? (msg.sender_name || '坐席') : '客戶' }}</span>
                <span v-if="msg.is_internal" class="internal-badge">內部備註</span>
                <span class="msg-time">{{ formatTime(msg.created_at) }}</span>
              </div>
              <div class="msg-bubble" v-html="msg.content.replace(/\n/g, '<br/>')" />
              <div v-if="msg.attachment_url" class="msg-attachment">
                <el-link :href="msg.attachment_url" target="_blank" type="primary">
                  📎 {{ msg.attachment_name }}
                </el-link>
              </div>
            </div>
          </div>

          <!-- Reply box -->
          <div class="reply-box">
            <div class="reply-tabs">
              <el-radio-group v-model="replyMode" size="small">
                <el-radio-button value="reply">回覆客戶</el-radio-button>
                <el-radio-button value="internal">內部備註</el-radio-button>
              </el-radio-group>
              <div class="reply-actions">
                <el-dropdown trigger="click" @command="insertQuickReply">
                  <el-button size="small">快捷回覆 <el-icon><ArrowDown /></el-icon></el-button>
                  <template #dropdown>
                    <el-dropdown-menu>
                      <el-dropdown-item v-for="qr in quickReplies" :key="qr.id" :command="qr.content">
                        {{ qr.title }}
                      </el-dropdown-item>
                    </el-dropdown-menu>
                  </template>
                </el-dropdown>
                <el-upload :show-file-list="false" :before-upload="handleFileUpload" accept=".jpg,.jpeg,.png,.pdf,.docx">
                  <el-button size="small" icon="Paperclip">附件</el-button>
                </el-upload>
              </div>
            </div>
            <el-input
              v-model="replyContent"
              type="textarea"
              :rows="4"
              :placeholder="replyMode === 'internal' ? '輸入內部備註（客戶不可見）...' : '輸入回覆內容...'"
              :class="{ 'internal-textarea': replyMode === 'internal' }"
            />
            <div class="reply-footer">
              <el-button type="primary" :loading="sending" @click="sendReply">
                {{ replyMode === 'internal' ? '儲存備註' : '發送回覆' }}
              </el-button>
            </div>
          </div>
        </div>

        <!-- Info panel -->
        <div class="info-panel">
          <!-- Ticket info -->
          <el-card shadow="never" class="info-card">
            <template #header><span class="card-title">工單資訊</span></template>
            <div class="info-row"><label>優先級</label>
              <el-tag :type="priorityType(ticketStore.current.priority)" size="small">{{ priorityLabel(ticketStore.current.priority) }}</el-tag>
            </div>
            <div class="info-row"><label>分類</label><span>{{ ticketStore.current.category?.name || '—' }}</span></div>
            <div class="info-row"><label>SLA</label>
              <span :class="{ 'sla-overdue': isSlaOverdue }">{{ ticketStore.current.sla_due_at ? formatTime(ticketStore.current.sla_due_at) : '—' }}</span>
            </div>
            <div class="info-row"><label>負責坐席</label><span>{{ ticketStore.current.agent?.name || '未分派' }}</span></div>
            <div class="info-row"><label>建立時間</label><span>{{ formatTime(ticketStore.current.created_at) }}</span></div>
          </el-card>

          <!-- Customer info -->
          <el-card shadow="never" class="info-card" v-if="ticketStore.current.customer">
            <template #header><span class="card-title">客戶資訊</span></template>
            <div class="info-row"><label>姓名</label><span>{{ ticketStore.current.customer.name }}</span></div>
            <div class="info-row"><label>Email</label><span>{{ ticketStore.current.customer.email || '—' }}</span></div>
            <div class="info-row"><label>電話</label><span>{{ ticketStore.current.customer.phone || '—' }}</span></div>
            <div class="info-row"><label>公司</label><span>{{ ticketStore.current.customer.company || '—' }}</span></div>
          </el-card>

          <!-- CSAT -->
          <el-card shadow="never" class="info-card" v-if="ticketStore.current.status === 'resolved'">
            <template #header><span class="card-title">客戶滿意度</span></template>
            <el-rate v-model="csatScore" @change="submitCsat" />
          </el-card>
        </div>
      </div>
    </template>

    <!-- Transfer dialog -->
    <el-dialog v-model="showTransfer" title="轉接工單" width="400px">
      <el-select v-model="transferAgent" placeholder="選擇坐席" style="width: 100%">
        <el-option v-for="u in agents" :key="u.id" :label="u.name" :value="u.id" />
      </el-select>
      <template #footer>
        <el-button @click="showTransfer = false">取消</el-button>
        <el-button type="primary" @click="doTransfer">確認轉接</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { useTicketStore } from '@/stores/tickets'
import { ticketApi, quickReplyApi, userApi } from '@/api'
import dayjs from 'dayjs'

const route       = useRoute()
const ticketStore = useTicketStore()

const replyContent  = ref('')
const replyMode     = ref('reply')
const sending       = ref(false)
const scrollRef     = ref()
const quickReplies  = ref([])
const agents        = ref([])
const showTransfer  = ref(false)
const transferAgent = ref(null)
const csatScore     = ref(0)
const pendingFile   = ref(null)

const isSlaOverdue = computed(() => {
  const t = ticketStore.current
  return t?.sla_due_at && dayjs(t.sla_due_at).isBefore(dayjs())
})

function formatTime(dt) { return dayjs(dt).format('MM/DD HH:mm') }
function priorityLabel(p) { return { urgent: '緊急', high: '高', medium: '中', low: '低' }[p] || p }
function priorityType(p) { return { urgent: 'danger', high: 'warning', medium: '', low: 'info' }[p] || '' }

function insertQuickReply(content) { replyContent.value = content }

async function handleFileUpload(file) {
  pendingFile.value = file
  return false
}

async function sendReply() {
  if (!replyContent.value.trim() && !pendingFile.value) return
  sending.value = true
  try {
    let data
    if (pendingFile.value) {
      data = new FormData()
      data.append('content', replyContent.value)
      data.append('attachment', pendingFile.value)
      data.append('is_internal', replyMode.value === 'internal' ? '1' : '0')
    } else {
      data = { content: replyContent.value, is_internal: replyMode.value === 'internal' }
    }
    await ticketStore.sendMessage(ticketStore.current.id, data)
    replyContent.value = ''
    pendingFile.value  = null
    await nextTick()
    scrollRef.value?.scrollTo({ top: scrollRef.value.scrollHeight, behavior: 'smooth' })
  } finally {
    sending.value = false
  }
}

async function changeStatus(status) {
  await ticketApi.updateStatus(ticketStore.current.id, status)
  ticketStore.current.status = status
}

async function changePriority(priority) {
  await ticketApi.update(ticketStore.current.id, { priority })
  ticketStore.current.priority = priority
}

async function doTransfer() {
  if (!transferAgent.value) return
  await ticketApi.transfer(ticketStore.current.id, transferAgent.value)
  showTransfer.value = false
  await ticketStore.fetchTicket(route.params.id)
  ElMessage.success('工單已轉接')
}

async function submitCsat(score) {
  await ticketApi.update(ticketStore.current.id, { csat_score: score })
  ElMessage.success('感謝您的評分！')
}

watch(() => ticketStore.messages.length, async () => {
  await nextTick()
  scrollRef.value?.scrollTo({ top: scrollRef.value.scrollHeight, behavior: 'smooth' })
})

onMounted(async () => {
  await ticketStore.fetchTicket(route.params.id)
  await ticketStore.fetchMessages(route.params.id)
  const qrRes = await quickReplyApi.list()
  quickReplies.value = qrRes.data
  const uRes = await userApi.list()
  agents.value = uRes.data.filter(u => u.role === 'agent')
  csatScore.value = ticketStore.current?.csat_score || 0
})
</script>

<style scoped>
.ticket-detail { height: calc(100vh - 60px); display: flex; flex-direction: column; }
.detail-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 16px 24px; background: #fff; border-bottom: 1px solid #e2e8f0;
}
.header-left { display: flex; align-items: center; gap: 12px; }
.ticket-no { font-family: monospace; color: #4F46E5; font-weight: 700; font-size: 13px; }
.ticket-subject { font-size: 16px; font-weight: 600; color: #1e293b; margin: 2px 0 0; }
.header-actions { display: flex; gap: 8px; }
.detail-body { flex: 1; display: flex; overflow: hidden; }

.messages-panel { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
.messages-scroll { flex: 1; overflow-y: auto; padding: 20px 24px; }

.msg-item { margin-bottom: 20px; }
.msg-item.internal .msg-bubble { background: #fef9c3; border-left: 3px solid #eab308; }
.msg-meta { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
.msg-sender { font-weight: 600; color: #1e293b; font-size: 13px; }
.internal-badge { background: #fef9c3; color: #92400e; border-radius: 4px; padding: 1px 6px; font-size: 11px; }
.msg-time { color: #94a3b8; font-size: 12px; }
.msg-bubble { background: #f1f5f9; border-radius: 12px; padding: 12px 16px; line-height: 1.6; color: #374151; max-width: 680px; }
.msg-item.customer .msg-bubble { background: #eff6ff; }
.msg-attachment { margin-top: 6px; }

.reply-box { border-top: 1px solid #e2e8f0; padding: 16px 24px; background: #fff; }
.reply-tabs { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
.reply-actions { display: flex; gap: 8px; }
.internal-textarea :deep(textarea) { background: #fefce8; }
.reply-footer { display: flex; justify-content: flex-end; margin-top: 10px; }

.info-panel { width: 300px; border-left: 1px solid #e2e8f0; overflow-y: auto; background: #fff; }
.info-card { border: none; border-bottom: 1px solid #f1f5f9; border-radius: 0; }
.card-title { font-weight: 600; font-size: 14px; }
.info-row { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; font-size: 13px; color: #374151; }
.info-row label { color: #94a3b8; }
.sla-overdue { color: #ef4444; font-weight: 600; }
</style>
