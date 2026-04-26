<template>
  <div class="ticket-list-page">
    <!-- Toolbar -->
    <div class="page-header">
      <div class="header-left">
        <h2>{{ t('nav.inbox') }}</h2>
        <el-tag type="info">{{ ticketStore.tickets.length }} 件</el-tag>
      </div>
      <div class="header-right">
        <el-input v-model="search" :placeholder="t('common.search')" clearable prefix-icon="Search" style="width: 220px" @input="onSearch" />
        <el-select v-model="filterStatus" placeholder="狀態" clearable @change="onFilter" style="width: 120px">
          <el-option label="待處理" value="open" />
          <el-option label="跟進中" value="pending" />
          <el-option label="已解決" value="resolved" />
          <el-option label="已關閉" value="closed" />
        </el-select>
        <el-select v-model="filterPriority" placeholder="優先級" clearable @change="onFilter" style="width: 120px">
          <el-option label="緊急" value="urgent" />
          <el-option label="高" value="high" />
          <el-option label="中" value="medium" />
          <el-option label="低" value="low" />
        </el-select>
        <el-button type="primary" icon="Plus" @click="showCreate = true">{{ t('ticket.create') }}</el-button>
      </div>
    </div>

    <!-- Filter tabs -->
    <div class="filter-tabs">
      <el-radio-group v-model="tabFilter" @change="onFilter">
        <el-radio-button value="">全部工單</el-radio-button>
        <el-radio-button value="mine">我的工單</el-radio-button>
        <el-radio-button value="open">待處理</el-radio-button>
        <el-radio-button value="resolved">已解決</el-radio-button>
      </el-radio-group>
    </div>

    <!-- Ticket table -->
    <el-card class="ticket-table-card" shadow="never">
      <el-table
        :data="ticketStore.tickets"
        v-loading="ticketStore.loading"
        row-key="id"
        @row-click="openTicket"
        class="ticket-table"
        stripe
      >
        <el-table-column label="工單編號" prop="ticket_no" width="160">
          <template #default="{ row }">
            <span class="ticket-no">{{ row.ticket_no }}</span>
          </template>
        </el-table-column>
        <el-table-column label="主題" prop="subject" min-width="220" show-overflow-tooltip />
        <el-table-column label="客戶" width="130">
          <template #default="{ row }">{{ row.customer?.name || '—' }}</template>
        </el-table-column>
        <el-table-column label="狀態" width="100">
          <template #default="{ row }">
            <el-tag :type="statusType(row.status)" size="small">{{ statusLabel(row.status) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="優先級" width="90">
          <template #default="{ row }">
            <el-tag :type="priorityType(row.priority)" size="small">{{ priorityLabel(row.priority) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="負責坐席" width="120">
          <template #default="{ row }">{{ row.agent?.name || '未分派' }}</template>
        </el-table-column>
        <el-table-column label="SLA" width="130">
          <template #default="{ row }">
            <span v-if="row.sla_due_at" :class="{ 'sla-overdue': isSlaOverdue(row) }">
              {{ formatSla(row.sla_due_at) }}
            </span>
          </template>
        </el-table-column>
        <el-table-column label="建立時間" width="150">
          <template #default="{ row }">{{ formatDate(row.created_at) }}</template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- Create ticket dialog -->
    <CreateTicketDialog v-model="showCreate" @created="onTicketCreated" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useTicketStore } from '@/stores/tickets'
import CreateTicketDialog from '@/components/tickets/CreateTicketDialog.vue'
import { useDebounceFn } from '@vueuse/core'
import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'
dayjs.extend(relativeTime)

const { t }       = useI18n()
const router      = useRouter()
const ticketStore = useTicketStore()

const showCreate    = ref(false)
const search        = ref('')
const filterStatus  = ref('')
const filterPriority = ref('')
const tabFilter     = ref('')

function buildParams() {
  const p = {}
  if (search.value) p.search = search.value
  if (filterStatus.value) p.status = filterStatus.value
  if (filterPriority.value) p.priority = filterPriority.value
  if (tabFilter.value === 'mine') p.assigned_to_me = true
  else if (tabFilter.value && tabFilter.value !== 'mine') p.status = tabFilter.value
  return p
}

const onSearch = useDebounceFn(() => ticketStore.fetchTickets(buildParams()), 400)
function onFilter() { ticketStore.fetchTickets(buildParams()) }
function openTicket(row) { router.push(`/tickets/${row.id}`) }
function onTicketCreated() { ticketStore.fetchTickets(buildParams()) }

function statusLabel(s) {
  return { open: '待處理', pending: '跟進中', resolved: '已解決', closed: '已關閉' }[s] || s
}
function statusType(s) {
  return { open: 'danger', pending: 'warning', resolved: 'success', closed: 'info' }[s] || ''
}
function priorityLabel(p) {
  return { urgent: '緊急', high: '高', medium: '中', low: '低' }[p] || p
}
function priorityType(p) {
  return { urgent: 'danger', high: 'warning', medium: '', low: 'info' }[p] || ''
}
function isSlaOverdue(row) {
  return row.sla_due_at && dayjs(row.sla_due_at).isBefore(dayjs())
}
function formatSla(dt) {
  return dayjs(dt).fromNow()
}
function formatDate(dt) {
  return dayjs(dt).format('MM/DD HH:mm')
}

onMounted(() => ticketStore.fetchTickets())
</script>

<style scoped>
.ticket-list-page { padding: 24px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.header-left { display: flex; align-items: center; gap: 12px; }
.header-left h2 { font-size: 20px; font-weight: 600; color: #1e293b; }
.header-right { display: flex; gap: 8px; }
.filter-tabs { margin-bottom: 16px; }
.ticket-table-card { border-radius: 12px; }
.ticket-table { cursor: pointer; }
.ticket-no { font-family: monospace; color: #4F46E5; font-weight: 600; }
.sla-overdue { color: #ef4444; font-weight: 600; }
</style>
