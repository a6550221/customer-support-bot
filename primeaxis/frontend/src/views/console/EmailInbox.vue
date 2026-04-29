<template>
  <div class="email-inbox">
    <!-- Email List -->
    <div class="email-list">
      <div class="email-list-header">
        <div class="section-label">郵件收件箱 Email Inbox</div>
        <div class="search-bar">
          <el-icon><Search /></el-icon>
          <input v-model="search" placeholder="搜尋郵件..." />
        </div>
      </div>
      <div class="emails-scroll">
        <div
          v-for="e in filteredEmails"
          :key="e.id"
          :class="['email-item', selected?.id === e.id && 'active', e.urgent && 'urgent']"
          @click="selectEmail(e)"
        >
          <div class="email-item-top">
            <span class="from-name">{{ e.from }}</span>
            <span class="email-time">{{ e.time }}</span>
          </div>
          <div class="email-subject">{{ e.subject }}</div>
          <div class="email-preview">{{ e.preview }}</div>
          <div class="email-meta">
            <span class="linked-order">{{ e.linkedOrder }}</span>
            <span :class="['email-status', sentIds.includes(e.id) ? 'replied' : e.status === '已回覆' ? 'replied' : 'pending']">
              {{ sentIds.includes(e.id) ? '已回覆' : e.status }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Email Detail + Reply -->
    <div v-if="selected" class="email-detail">
      <div class="detail-header">
        <div class="detail-subject">{{ selected.subject }}</div>
        <div class="detail-meta">
          <div class="sender-avatar">{{ selected.from[0] }}</div>
          <div>
            <div class="sender-name">{{ selected.from }} <span class="sender-email">&lt;{{ selected.email }}&gt;</span></div>
            <div class="detail-to">收件人：info@primeaxisglobal.org · {{ selected.time }}</div>
          </div>
          <div class="order-link-tag">✦ {{ selected.linkedOrder }} 自動關聯</div>
        </div>
      </div>

      <div class="detail-scroll">
        <div class="email-body">{{ selected.body }}</div>

        <div class="reply-box">
          <div class="reply-box-header">
            <span>回覆 Reply to: <span class="reply-to-email">{{ selected.email }}</span></span>
            <button class="axi-draft-btn" @click="axiDraft" :disabled="drafting">
              {{ drafting ? '⟳ Axi 草擬中...' : '✦ Axi 智能草擬' }}
            </button>
          </div>
          <textarea
            v-model="replyText"
            class="reply-textarea"
            placeholder="輸入回覆內容，或點擊「Axi 智能草擬」..."
            rows="8"
          />
          <div class="reply-footer">
            <span class="reply-from">發件人：info@primeaxisglobal.org · PrimeAxis 客服團隊</span>
            <div class="reply-actions">
              <button class="draft-btn">存稿 Draft</button>
              <button class="send-btn" @click="sendReply" :class="{ sent: sentIds.includes(selected.id) }">
                {{ sentIds.includes(selected.id) ? '✓ 已發送' : '↑ 發送回覆 Send' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="email-empty">
      <div class="empty-icon">✉</div>
      <div>選擇郵件查看詳情</div>
      <div class="empty-sub">Select an email to view</div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Search } from '@element-plus/icons-vue'

const search  = ref('')
const selected = ref(null)
const replyText = ref('')
const drafting  = ref(false)
const sentIds   = ref([])

const emails = [
  {
    id: 1, from: '張美玲', email: 'mei.zhang@example.com',
    subject: '關於訂單 PA-2024-0890 的派送問題',
    preview: '你好，我想了解我的貨物狀況...',
    body: '你好，\n\n我想了解一下我的貨物狀況。我的訂單號是 PA-2024-0890，從香港發往北京，但到現在還沒有收到貨物，而且系統顯示「異常」狀態。\n\n請問是什麼問題？什麼時候可以送達？\n\n謝謝\n張美玲',
    time: '10:28', linkedOrder: 'PA-2024-0890', status: '未回覆', urgent: true,
  },
  {
    id: 2, from: 'Global Trade Ltd', email: 'ops@globaltrade.hk',
    subject: 'Shipment PA-2024-0891 - Pickup Confirmation',
    preview: 'Hi, Could you confirm the pickup time...',
    body: 'Hi PrimeAxis Team,\n\nCould you please confirm the pickup time for our shipment PA-2024-0891 (GZ → HK, 45kg)? We need to arrange staff to be present.\n\nBest regards,\nGlobal Trade Ltd Operations',
    time: '09:45', linkedOrder: 'PA-2024-0891', status: '未回覆', urgent: false,
  },
  {
    id: 3, from: '陳志豪', email: 'chanz@mail.com',
    subject: '查詢台灣訂單預計抵達時間',
    preview: '你好，我的訂單PA-2024-0888到台灣大概幾時到...',
    body: '你好，\n\n我的訂單 PA-2024-0888 從香港寄往台灣，請問預計幾時到達？謝謝。\n\n陳志豪',
    time: '昨日 17:20', linkedOrder: 'PA-2024-0888', status: '已回覆', urgent: false,
  },
]

const axiDrafts = {
  1: '親愛的張美玲女士，\n\n感謝您聯繫 PrimeAxis。\n\n關於您的訂單 PA-2024-0890（香港→北京），我們的系統顯示貨物目前處於「異常」狀態，原因為派送地址資訊不完整。我們的客服專員已正在處理此問題。\n\n為了盡快安排重新派送，請您確認收件完整地址及聯繫電話。預計在地址確認後24小時內完成派送。\n\n此致\nPrimeAxis 客服團隊\ninfo@primeaxisglobal.org',
  2: 'Dear Global Trade Ltd Team,\n\nThank you for contacting PrimeAxis.\n\nRegarding shipment PA-2024-0891 (GZ → HK, 45kg), the scheduled pickup time is today between 14:00–17:00 at your Guangzhou warehouse address on record.\n\nPlease ensure someone is available during this window.\n\nBest regards,\nPrimeAxis Customer Service\ninfo@primeaxisglobal.org',
  3: '親愛的陳志豪先生，\n\n感謝您的查詢。\n\n您的訂單 PA-2024-0888 目前正在運輸中，預計2-3個工作日內抵達台灣，即最遲於近日送達。\n\nPrimeAxis 客服團隊\ninfo@primeaxisglobal.org',
}

const filteredEmails = computed(() => {
  if (!search.value) return emails
  const q = search.value.toLowerCase()
  return emails.filter(e =>
    e.from.toLowerCase().includes(q) ||
    e.subject.toLowerCase().includes(q) ||
    e.linkedOrder.toLowerCase().includes(q)
  )
})

function selectEmail(e) { selected.value = e; replyText.value = ''; drafting.value = false }

function axiDraft() {
  if (!selected.value) return
  drafting.value = true
  replyText.value = ''
  const target = axiDrafts[selected.value.id] || ''
  let i = 0
  const iv = setInterval(() => {
    i += 4
    replyText.value = target.slice(0, i)
    if (i >= target.length) { clearInterval(iv); drafting.value = false }
  }, 12)
}

function sendReply() {
  if (!selected.value) return
  sentIds.value.push(selected.value.id)
}
</script>

<style scoped>
.email-inbox { flex: 1; display: flex; overflow: hidden; }

.email-list {
  width: 300px; background: #fff; border-right: 1px solid var(--pa-border);
  display: flex; flex-direction: column; overflow: hidden; flex-shrink: 0;
}
.email-list-header { padding: 14px 16px; border-bottom: 1px solid var(--pa-border); }
.section-label { font-size: 11px; font-weight: 700; color: #9e9890; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
.search-bar {
  display: flex; align-items: center; gap: 6px;
  background: #faf8f5; border: 1px solid var(--pa-border); border-radius: 7px; padding: 6px 10px;
}
.search-bar input { border: none; background: transparent; font-size: 12px; outline: none; font-family: inherit; width: 100%; }
.emails-scroll { flex: 1; overflow-y: auto; }

.email-item {
  padding: 12px 14px; border-bottom: 1px solid #faf8f5; cursor: pointer;
  border-left: 3px solid transparent; transition: background 0.12s;
}
.email-item:hover { background: #fdf5ec; }
.email-item.active { background: #fdf5ec; border-left-color: var(--pa-orange); }
.email-item.urgent { border-left-color: var(--pa-danger) !important; }
.email-item-top { display: flex; justify-content: space-between; margin-bottom: 3px; }
.from-name  { font-size: 13px; font-weight: 700; color: #2c2520; }
.email-time { font-size: 10px; color: #9e9890; }
.email-subject { font-size: 12px; font-weight: 600; color: #4a4540; margin-bottom: 3px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.email-preview { font-size: 11px; color: #9e9890; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-bottom: 5px; }
.email-meta { display: flex; align-items: center; gap: 6px; }
.linked-order { font-size: 10px; font-family: monospace; color: var(--pa-orange); font-weight: 700; }
.email-status { font-size: 9px; padding: 1px 7px; border-radius: 10px; font-weight: 600; }
.email-status.pending { background: #fff3e6; color: #e8851a; }
.email-status.replied { background: #e6f7ef; color: #2a9d5c; }

/* Detail */
.email-detail { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
.detail-header { padding: 16px 20px; background: #fff; border-bottom: 1px solid var(--pa-border); flex-shrink: 0; }
.detail-subject { font-size: 15px; font-weight: 700; color: #2c2520; margin-bottom: 8px; }
.detail-meta { display: flex; align-items: center; gap: 12px; }
.sender-avatar {
  width: 28px; height: 28px; border-radius: 50%;
  background: var(--pa-orange); color: #fff; font-size: 11px; font-weight: 700;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.sender-name { font-size: 12px; font-weight: 600; color: #2c2520; }
.sender-email { font-weight: 400; color: #9e9890; }
.detail-to { font-size: 11px; color: #9e9890; }
.order-link-tag {
  margin-left: auto; padding: 4px 10px; background: var(--pa-orange-light);
  border: 1px solid var(--pa-orange-border); border-radius: 6px;
  font-size: 10px; color: var(--pa-orange); font-weight: 700;
}

.detail-scroll { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 16px; }
.email-body {
  background: #fff; border-radius: 10px; padding: 16px 20px;
  border: 1px solid var(--pa-border); white-space: pre-wrap;
  font-size: 13px; line-height: 1.7; color: #2c2520;
}

.reply-box { background: #fff; border-radius: 10px; border: 1px solid var(--pa-border); overflow: hidden; }
.reply-box-header {
  padding: 10px 14px; background: #faf8f5; border-bottom: 1px solid var(--pa-border);
  display: flex; align-items: center; justify-content: space-between;
  font-size: 12px; font-weight: 700; color: #4a4540;
}
.reply-to-email { color: #9e9890; font-weight: 400; }
.axi-draft-btn {
  padding: 5px 12px; border-radius: 7px;
  border: 1.5px solid #f0e8d8;
  background: linear-gradient(135deg, #fdf5ec, #f0faf5);
  color: #b36a10; font-size: 12px; font-weight: 700; cursor: pointer;
}
.axi-draft-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.reply-textarea {
  width: 100%; border: none; padding: 14px 16px;
  font-size: 13px; font-family: inherit; resize: vertical;
  color: #2c2520; line-height: 1.6; outline: none;
}
.reply-footer {
  padding: 10px 14px; border-top: 1px solid var(--pa-border);
  display: flex; align-items: center; justify-content: space-between;
}
.reply-from { font-size: 11px; color: #9e9890; }
.reply-actions { display: flex; gap: 8px; }
.draft-btn {
  padding: 6px 14px; border-radius: 7px; background: #f5f2ee;
  border: none; color: #706560; font-size: 12px; font-weight: 600; cursor: pointer;
}
.send-btn {
  padding: 6px 16px; border-radius: 7px;
  background: var(--pa-orange); border: none; color: #fff;
  font-size: 12px; font-weight: 700; cursor: pointer;
}
.send-btn.sent { background: #2a9d5c; }

/* Empty state */
.email-empty {
  flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 8px; color: #9e9890;
}
.empty-icon { font-size: 40px; opacity: 0.25; }
.empty-sub  { font-size: 11px; opacity: 0.7; }
</style>
