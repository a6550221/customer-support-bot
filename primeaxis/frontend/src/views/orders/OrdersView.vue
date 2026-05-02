<template>
  <div class="orders-view">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">訂單管理 Order Management</h1>
        <p class="page-sub">共 {{ filteredOrders.length }} 筆訂單</p>
      </div>
      <el-button type="primary" :icon="Plus" @click="showAddDialog = true">新增訂單</el-button>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
      <el-input v-model="search" placeholder="搜尋訂單號/客戶..." :prefix-icon="Search" clearable style="width:220px" />
      <el-select
        v-model="filterStatus"
        multiple
        collapse-tags
        collapse-tags-tooltip
        placeholder="所有狀態"
        clearable
        style="width:160px"
      >
        <el-option label="運輸中" value="transit" />
        <el-option label="待取件" value="pending" />
        <el-option label="已派送" value="active" />
        <el-option label="異常"   value="exception" />
        <el-option label="已結單" value="closed" />
      </el-select>
      <el-checkbox v-model="hideCompleted" class="hide-done-check">隱藏已結單</el-checkbox>
      <el-button :icon="Refresh" @click="init" :loading="loading" />
    </div>

    <!-- Orders Table -->
    <div class="table-wrap">
      <el-table
        v-loading="loading"
        :data="filteredOrders"
        @row-click="openDrawer"
        row-class-name="hover-row"
        style="width: 100%"
        height="100%"
      >
        <el-table-column prop="order_no" label="訂單號" width="155">
          <template #default="{ row }"><span class="order-no">{{ row.order_no }}</span></template>
        </el-table-column>
        <el-table-column prop="customer_name" label="客戶" width="120" />
        <el-table-column prop="route" label="路線" width="110" />
        <el-table-column prop="weight" label="重量" width="85" />
        <el-table-column label="狀態" width="90">
          <template #default="{ row }">
            <span :class="'status-' + row.status">{{ STATUS_LABEL[row.status] || row.status }}</span>
          </template>
        </el-table-column>
        <el-table-column label="負責客服" width="90">
          <template #default="{ row }">{{ row.assignee_name || '—' }}</template>
        </el-table-column>
        <el-table-column label="建立日期">
          <template #default="{ row }">{{ fmtDate(row.created_at) }}</template>
        </el-table-column>
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button size="small" @click.stop="openDrawer(row)">查看</el-button>
            <el-select
              :model-value="row.status"
              size="small"
              style="width:82px"
              @change="v => quickStatus(row, v)"
              @click.stop
            >
              <el-option label="運輸中" value="transit" />
              <el-option label="待取件" value="pending" />
              <el-option label="已派送" value="active" />
              <el-option label="異常"   value="exception" />
              <el-option label="已結單" value="closed" />
            </el-select>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- Order Detail Drawer -->
    <el-drawer v-model="drawerOpen" :title="'訂單 ' + (current?.order_no || '')" size="500px" direction="rtl">
      <div v-if="current" class="drawer-body">

        <!-- Basic Info -->
        <div class="drawer-section">
          <div class="drawer-section-title">基本資訊</div>
          <div class="detail-grid">
            <div class="detail-row"><span class="dl">訂單號</span><span class="dv order-no">{{ current.order_no }}</span></div>
            <div class="detail-row"><span class="dl">客戶</span><span class="dv">{{ current.customer_name }}</span></div>
            <div class="detail-row"><span class="dl">路線</span><span class="dv">{{ current.route }}</span></div>
            <div class="detail-row"><span class="dl">重量</span><span class="dv">{{ current.weight }}</span></div>
            <div class="detail-row">
              <span class="dl">狀態</span>
              <span :class="'status-' + current.status">{{ STATUS_LABEL[current.status] }}</span>
            </div>
            <div class="detail-row"><span class="dl">負責客服</span><span class="dv">{{ current.assignee_name || '—' }}</span></div>
            <div class="detail-row" v-if="current.notes">
              <span class="dl">備注</span><span class="dv note-text">{{ current.notes }}</span>
            </div>
          </div>
        </div>

        <!-- Update Status -->
        <div class="drawer-section">
          <div class="drawer-section-title">更新狀態</div>
          <div style="display:flex;gap:8px">
            <el-select v-model="editStatus" size="small" style="flex:1">
              <el-option label="運輸中" value="transit" />
              <el-option label="待取件" value="pending" />
              <el-option label="已派送" value="active" />
              <el-option label="異常"   value="exception" />
              <el-option label="已結單" value="closed" />
            </el-select>
            <el-button type="primary" size="small" @click="saveStatus" :loading="savingStatus">更新</el-button>
          </div>
          <el-input
            v-model="statusNote"
            placeholder="狀態備注（可選，會自動記錄到時間軸）..."
            size="small"
            style="margin-top:8px"
          />
        </div>

        <!-- Tracking Timeline -->
        <div class="drawer-section">
          <div class="drawer-section-title timeline-header">
            <span>物流時間軸 Tracking</span>
            <el-button size="small" text type="primary" @click="showAddEvent = !showAddEvent">
              <el-icon style="margin-right:2px"><Plus /></el-icon>新增節點
            </el-button>
          </div>

          <!-- Add Event Form -->
          <div v-if="showAddEvent" class="add-event-form">
            <el-input
              v-model="newEvent.text"
              placeholder="輸入物流節點說明（例：已電話聯繫客戶確認收件地址）"
              type="textarea"
              :rows="2"
              size="small"
            />
            <div style="display:flex;gap:8px;margin-top:6px;align-items:center">
              <el-select v-model="newEvent.type" size="small" style="width:120px">
                <el-option label="⚪ 一般更新" value="primary" />
                <el-option label="🟢 完成/簽收" value="success" />
                <el-option label="🟡 注意事項" value="warning" />
                <el-option label="🔴 異常/問題" value="danger" />
              </el-select>
              <el-button type="primary" size="small" @click="addEvent" :loading="addingEvent" style="flex:1">確認新增</el-button>
              <el-button size="small" @click="showAddEvent = false">取消</el-button>
            </div>
          </div>

          <!-- Timeline List -->
          <div v-if="timelineLoading" class="timeline-loading">
            <span style="color:#9e9890;font-size:12px">載入中...</span>
          </div>
          <el-timeline v-else>
            <el-timeline-item
              v-for="t in timeline"
              :key="t.id"
              :timestamp="fmtDatetime(t.created_at)"
              :type="t.type || 'primary'"
              placement="top"
            >
              <div class="tl-row">
                <span class="tl-text">{{ t.text }}</span>
                <span class="tl-editor">{{ t.editor_name }}</span>
              </div>
            </el-timeline-item>
            <el-timeline-item v-if="!timeline.length" type="" timestamp="—">
              <span style="color:#9e9890;font-size:12px">暫無追蹤記錄</span>
            </el-timeline-item>
          </el-timeline>
        </div>

      </div>
    </el-drawer>

    <!-- Add Order Dialog -->
    <el-dialog v-model="showAddDialog" title="新增訂單" width="500px">
      <el-form :model="newOrder" label-width="80px" size="small">
        <el-form-item label="客戶名稱"><el-input v-model="newOrder.customer_name" /></el-form-item>
        <el-form-item label="發貨城市">
          <el-select v-model="newOrder.from_city" style="width:100%">
            <el-option v-for="c in cities" :key="c" :label="c" :value="c" />
          </el-select>
        </el-form-item>
        <el-form-item label="目的城市">
          <el-select v-model="newOrder.to_city" style="width:100%">
            <el-option v-for="c in cities" :key="c" :label="c" :value="c" />
          </el-select>
        </el-form-item>
        <el-form-item label="重量(kg)"><el-input-number v-model="newOrder.weight" :min="0.1" :step="0.5" /></el-form-item>
        <el-form-item label="備注"><el-input v-model="newOrder.notes" type="textarea" :rows="3" /></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showAddDialog = false">取消</el-button>
        <el-button type="primary" @click="addOrder" :loading="creatingOrder">建立訂單</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus, Search, Refresh } from '@element-plus/icons-vue'
import { orderApi } from '@/api/index.js'

const STATUS_LABEL = {
  transit:   '運輸中',
  pending:   '待取件',
  active:    '已派送',
  exception: '異常',
  closed:    '已結單',
}

// ── State ──────────────────────────────────────────────────────────────────
const loading       = ref(false)
const search        = ref('')
const filterStatus  = ref([])   // multi-select array
const hideCompleted = ref(false)
const orders        = ref([])

const drawerOpen    = ref(false)
const showAddDialog = ref(false)
const current       = ref(null)
const editStatus    = ref('')
const statusNote    = ref('')
const savingStatus  = ref(false)

const timeline        = ref([])
const timelineLoading = ref(false)
const showAddEvent    = ref(false)
const newEvent        = ref({ text: '', type: 'primary' })
const addingEvent     = ref(false)

const newOrder      = ref({ customer_name: '', from_city: '香港', to_city: '北京', weight: 1, notes: '' })
const creatingOrder = ref(false)
const cities = ['香港', '廣州', '北京', '上海', '台灣', '深圳', '新加坡']

// ── Helpers ────────────────────────────────────────────────────────────────
function fmtDate(iso) {
  if (!iso) return '—'
  return iso.slice(0, 10)
}
function fmtDatetime(iso) {
  if (!iso) return '—'
  return iso.replace('T', ' ').slice(0, 16)
}

// ── Computed ───────────────────────────────────────────────────────────────
const filteredOrders = computed(() => {
  return orders.value.filter(o => {
    const s = search.value.toLowerCase()
    const matchSearch = !s ||
      o.order_no.toLowerCase().includes(s) ||
      o.customer_name.toLowerCase().includes(s)
    // Multi-select: empty array = show all; otherwise must be in selected list
    const matchStatus = filterStatus.value.length === 0 || filterStatus.value.includes(o.status)
    // Hide completed toggle
    const matchHide = !hideCompleted.value || o.status !== 'closed'
    return matchSearch && matchStatus && matchHide
  })
})

// ── API calls ──────────────────────────────────────────────────────────────
async function init() {
  loading.value = true
  try {
    const res = await orderApi.list()
    orders.value = res.data || []
  } catch {
    ElMessage.error('載入訂單失敗')
  } finally {
    loading.value = false
  }
}

async function openDrawer(row) {
  current.value      = row
  editStatus.value   = row.status
  statusNote.value   = ''
  showAddEvent.value = false
  newEvent.value     = { text: '', type: 'primary' }
  drawerOpen.value   = true
  await loadTimeline(row.id)
}

async function loadTimeline(orderId) {
  timelineLoading.value = true
  timeline.value = []
  try {
    const res = await orderApi.timeline(orderId)
    timeline.value = res.data || []
  } catch {
    timeline.value = []
  } finally {
    timelineLoading.value = false
  }
}

async function saveStatus() {
  if (!current.value) return
  savingStatus.value = true
  try {
    await orderApi.updateStatus(current.value.id, editStatus.value, statusNote.value || null)
    current.value.status = editStatus.value
    const o = orders.value.find(x => x.id === current.value.id)
    if (o) o.status = editStatus.value
    statusNote.value = ''
    ElMessage.success('狀態已更新，時間軸已記錄')
    await loadTimeline(current.value.id)
  } catch {
    ElMessage.error('更新失敗')
  } finally {
    savingStatus.value = false
  }
}

async function addEvent() {
  if (!newEvent.value.text.trim()) {
    ElMessage.warning('請輸入物流節點說明')
    return
  }
  addingEvent.value = true
  try {
    await orderApi.addEvent(current.value.id, newEvent.value.text.trim(), newEvent.value.type)
    ElMessage.success('物流節點已新增')
    newEvent.value     = { text: '', type: 'primary' }
    showAddEvent.value = false
    await loadTimeline(current.value.id)
  } catch {
    ElMessage.error('新增失敗')
  } finally {
    addingEvent.value = false
  }
}

async function quickStatus(row, val) {
  try {
    await orderApi.updateStatus(row.id, val)
    row.status = val
    ElMessage.success(`${row.order_no} 狀態已更新`)
  } catch {
    ElMessage.error('更新失敗')
  }
}

async function addOrder() {
  if (!newOrder.value.customer_name.trim()) {
    ElMessage.warning('請輸入客戶名稱')
    return
  }
  creatingOrder.value = true
  try {
    await orderApi.create({
      customer_name: newOrder.value.customer_name,
      from_city:     newOrder.value.from_city,
      to_city:       newOrder.value.to_city,
      weight:        newOrder.value.weight,
      notes:         newOrder.value.notes,
    })
    ElMessage.success('訂單已建立')
    showAddDialog.value = false
    newOrder.value = { customer_name: '', from_city: '香港', to_city: '北京', weight: 1, notes: '' }
    await init()
  } catch {
    ElMessage.error('建立失敗')
  } finally {
    creatingOrder.value = false
  }
}

onMounted(init)
</script>

<style scoped>
.orders-view  { height: 100%; display: flex; flex-direction: column; padding: 24px; gap: 16px; background: var(--pa-bg); overflow: hidden; }
.page-header  { display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; }
.page-title   { font-size: 20px; font-weight: 700; color: #2c2520; }
.page-sub     { font-size: 12px; color: #9e9890; margin-top: 2px; }
.filter-bar   { display: flex; gap: 10px; align-items: center; flex-shrink: 0; }
.table-wrap   { flex: 1; background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid var(--pa-border); }
.order-no     { font-family: monospace; font-weight: 700; color: var(--pa-orange); font-size: 12px; }

/* Drawer */
.drawer-body          { padding: 0 4px; }
.drawer-section       { margin-bottom: 24px; }
.drawer-section-title { font-size: 11px; font-weight: 700; color: #9e9890; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; padding-bottom: 6px; border-bottom: 1px solid var(--pa-border); }
.timeline-header      { display: flex; justify-content: space-between; align-items: center; }
.detail-grid          { display: flex; flex-direction: column; gap: 8px; }
.detail-row           { display: flex; gap: 12px; align-items: flex-start; }
.dl                   { font-size: 12px; color: #9e9890; width: 72px; flex-shrink: 0; padding-top: 1px; }
.dv                   { font-size: 13px; color: #2c2520; font-weight: 500; }
.note-text            { font-size: 12px; color: #4a4540; font-weight: 400; line-height: 1.5; }
.timeline-loading     { text-align: center; padding: 20px 0; }

/* Add event form */
.add-event-form {
  background: #faf8f5;
  border: 1px solid var(--pa-border);
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 16px;
}

/* Timeline row with editor tag */
.tl-row   { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; }
.tl-text  { flex: 1; font-size: 13px; color: #2c2520; line-height: 1.5; }
.tl-editor {
  flex-shrink: 0;
  font-size: 10px;
  color: #fff;
  background: #b09070;
  border-radius: 10px;
  padding: 1px 7px;
  white-space: nowrap;
  margin-top: 2px;
}

/* Hide-completed checkbox alignment */
.hide-done-check { margin-left: 4px; }
</style>
