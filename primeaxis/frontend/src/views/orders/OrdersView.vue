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
      <el-input v-model="search" placeholder="搜尋訂單號/客戶..." :prefix-icon="Search" clearable style="width:240px" />
      <el-select v-model="filterStatus" placeholder="所有狀態" clearable style="width:130px">
        <el-option label="運輸中" value="transit" />
        <el-option label="待取件" value="pending" />
        <el-option label="已派送" value="active" />
        <el-option label="異常"   value="exception" />
        <el-option label="已結單" value="closed" />
      </el-select>
      <el-select v-model="filterRoute" placeholder="所有路線" clearable style="width:130px">
        <el-option label="HK → BJ" value="HK-BJ" />
        <el-option label="HK → SH" value="HK-SH" />
        <el-option label="HK → TW" value="HK-TW" />
        <el-option label="GZ → HK" value="GZ-HK" />
      </el-select>
      <el-button :icon="Refresh" @click="init" :loading="loading" />
    </div>

    <!-- Orders Table -->
    <div class="table-wrap">
      <el-table
        :data="filteredOrders"
        @row-click="openDrawer"
        row-class-name="hover-row"
        style="width: 100%"
        height="100%"
      >
        <el-table-column prop="order_no" label="訂單號" width="150">
          <template #default="{ row }"><span class="order-no">{{ row.order_no }}</span></template>
        </el-table-column>
        <el-table-column prop="customer_name" label="客戶" width="110" />
        <el-table-column prop="route" label="路線" width="110" />
        <el-table-column prop="weight" label="重量" width="85" />
        <el-table-column prop="status" label="狀態" width="90">
          <template #default="{ row }">
            <span :class="'status-' + row.status_key">{{ row.status }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="assignee" label="負責客服" width="90" />
        <el-table-column prop="created_at" label="建立日期" />
        <el-table-column label="操作" width="130" fixed="right">
          <template #default="{ row }">
            <el-button size="small" @click.stop="openDrawer(row)">查看</el-button>
            <el-select
              :model-value="row.status_key"
              size="small"
              style="width:80px"
              @change="v => quickStatus(row, v)"
              @click.stop
            >
              <el-option label="運輸中" value="transit" />
              <el-option label="待取件" value="pending" />
              <el-option label="已派送" value="active" />
              <el-option label="異常"   value="exception" />
            </el-select>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- Order Detail Drawer -->
    <el-drawer v-model="drawerOpen" :title="'訂單 ' + (current?.order_no || '')" size="480px" direction="rtl">
      <div v-if="current" class="drawer-body">
        <!-- Order Info -->
        <div class="drawer-section">
          <div class="drawer-section-title">基本資訊</div>
          <div class="detail-grid">
            <div class="detail-row"><span class="dl">訂單號</span><span class="dv order-no">{{ current.order_no }}</span></div>
            <div class="detail-row"><span class="dl">客戶</span><span class="dv">{{ current.customer_name }}</span></div>
            <div class="detail-row"><span class="dl">路線</span><span class="dv">{{ current.route }}</span></div>
            <div class="detail-row"><span class="dl">重量</span><span class="dv">{{ current.weight }}</span></div>
            <div class="detail-row"><span class="dl">狀態</span>
              <span :class="'status-' + current.status_key">{{ current.status }}</span>
            </div>
            <div class="detail-row"><span class="dl">負責客服</span><span class="dv">{{ current.assignee }}</span></div>
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
            <el-button type="primary" size="small" @click="saveStatus">更新</el-button>
          </div>
          <el-input v-model="statusNote" placeholder="備注（可選）..." size="small" style="margin-top:8px" />
        </div>

        <!-- Tracking Timeline -->
        <div class="drawer-section">
          <div class="drawer-section-title">物流時間軸 Tracking</div>
          <el-timeline>
            <el-timeline-item
              v-for="t in timeline"
              :key="t.id"
              :timestamp="t.time"
              :type="t.type"
              placement="top"
            >
              {{ t.text }}
            </el-timeline-item>
          </el-timeline>
        </div>

        <!-- Conversation Archive -->
        <div class="drawer-section">
          <div class="drawer-section-title">對話歸檔 Conversation Archive</div>
          <div v-for="c in archive" :key="c.id" class="archive-item">
            <div class="archive-type">
              <span class="archive-icon">{{ c.type === 'call' ? '📞' : c.type === 'email' ? '✉' : '💬' }}</span>
              <span class="archive-label">{{ { call:'語音通話', email:'郵件', chat:'文字對話' }[c.type] }}</span>
            </div>
            <div class="archive-summary">{{ c.summary }}</div>
            <div class="archive-time">{{ c.time }} · {{ c.agent }}</div>
          </div>
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
        <el-button type="primary" @click="addOrder">建立訂單</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus, Search, Refresh } from '@element-plus/icons-vue'

const loading     = ref(false)
const search      = ref('')
const filterStatus = ref('')
const filterRoute  = ref('')
const drawerOpen  = ref(false)
const showAddDialog = ref(false)
const current     = ref(null)
const editStatus  = ref('')
const statusNote  = ref('')

const orders = ref([
  { id:1, order_no:'PA-2024-0893', customer_name:'李偉強',      route:'HK → SH', weight:'8.2 kg',  status:'運輸中', status_key:'transit',   assignee:'陳小明', created_at:'2024-11-27' },
  { id:2, order_no:'PA-2024-0892', customer_name:'ABC Corp',    route:'GZ → TW', weight:'24.5 kg', status:'待取件', status_key:'pending',   assignee:'王大華', created_at:'2024-11-27' },
  { id:3, order_no:'PA-2024-0891', customer_name:'Global Trade',route:'GZ → HK', weight:'45 kg',   status:'已取件', status_key:'active',    assignee:'陳小明', created_at:'2024-11-27' },
  { id:4, order_no:'PA-2024-0890', customer_name:'張美玲',      route:'HK → BJ', weight:'12.5 kg', status:'異常',   status_key:'exception', assignee:'陳小明', created_at:'2024-11-26' },
  { id:5, order_no:'PA-2024-0889', customer_name:'陳志豪',      route:'HK → TW', weight:'3.1 kg',  status:'已派送', status_key:'active',    assignee:'王大華', created_at:'2024-11-26' },
  { id:6, order_no:'PA-2024-0888', customer_name:'劉建國',      route:'SH → HK', weight:'18 kg',   status:'運輸中', status_key:'transit',   assignee:'林曉月', created_at:'2024-11-25' },
  { id:7, order_no:'PA-2024-0887', customer_name:'Sunrise Ltd', route:'HK → SG', weight:'67 kg',   status:'已結單', status_key:'closed',    assignee:'林曉月', created_at:'2024-11-24' },
])

const timeline = [
  { id:1, time:'2024-11-27 11:20', text:'貨物已到達香港倉庫',          type:'success' },
  { id:2, time:'2024-11-27 08:45', text:'快遞員已取件',                type:'primary' },
  { id:3, time:'2024-11-26 20:30', text:'訂單已確認，安排取件',        type:'' },
  { id:4, time:'2024-11-26 18:32', text:'訂單已建立',                  type:'' },
]

const archive = [
  { id:1, type:'call',  summary:'客戶來電查詢訂單狀態，已告知正在運輸中，預計2日內到達。', time:'2024-11-27 10:30', agent:'陳小明' },
  { id:2, type:'email', summary:'客戶郵件詢問取件時間，已回覆預計取件窗口為 14:00-17:00。', time:'2024-11-26 09:45', agent:'陳小明' },
  { id:3, type:'chat',  summary:'文字對話：確認收件地址及聯繫電話。',                        time:'2024-11-25 15:20', agent:'王大華' },
]

const filteredOrders = computed(() => {
  return orders.value.filter(o => {
    const matchSearch = !search.value || o.order_no.toLowerCase().includes(search.value.toLowerCase()) || o.customer_name.toLowerCase().includes(search.value.toLowerCase())
    const matchStatus = !filterStatus.value || o.status_key === filterStatus.value
    return matchSearch && matchStatus
  })
})

function openDrawer(row) {
  current.value  = row
  editStatus.value = row.status_key
  statusNote.value = ''
  drawerOpen.value = true
}

function saveStatus() {
  if (current.value) {
    current.value.status_key = editStatus.value
    current.value.status = { transit:'運輸中', pending:'待取件', active:'已派送', exception:'異常', closed:'已結單' }[editStatus.value]
    ElMessage.success('訂單狀態已更新')
  }
}

function quickStatus(row, val) {
  row.status_key = val
  row.status = { transit:'運輸中', pending:'待取件', active:'已派送', exception:'異常', closed:'已結單' }[val]
  ElMessage.success(`${row.order_no} 狀態已更新`)
}

const newOrder = ref({ customer_name:'', from_city:'香港', to_city:'北京', weight:1, notes:'' })
const cities = ['香港','廣州','北京','上海','台灣','深圳','新加坡']

function addOrder() {
  const id = orders.value.length + 1
  const no = 'PA-2024-0' + (900 + id)
  orders.value.unshift({
    id, order_no: no,
    customer_name: newOrder.value.customer_name || '未知客戶',
    route: `${newOrder.value.from_city} → ${newOrder.value.to_city}`,
    weight: newOrder.value.weight + ' kg',
    status: '待取件', status_key: 'pending',
    assignee: '陳小明', created_at: new Date().toISOString().slice(0,10),
  })
  showAddDialog.value = false
  ElMessage.success('訂單已建立')
}

function init() { /* fetch from API */ }
</script>

<style scoped>
.orders-view { height: 100%; display: flex; flex-direction: column; padding: 24px; gap: 16px; background: var(--pa-bg); overflow: hidden; }
.page-header  { display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; }
.page-title   { font-size: 20px; font-weight: 700; color: #2c2520; }
.page-sub     { font-size: 12px; color: #9e9890; margin-top: 2px; }
.filter-bar   { display: flex; gap: 10px; align-items: center; flex-shrink: 0; }
.table-wrap   { flex: 1; background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid var(--pa-border); }
.order-no { font-family: monospace; font-weight: 700; color: var(--pa-orange); font-size: 12px; }

/* Drawer */
.drawer-body { padding: 0 4px; }
.drawer-section { margin-bottom: 24px; }
.drawer-section-title { font-size: 11px; font-weight: 700; color: #9e9890; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; padding-bottom: 6px; border-bottom: 1px solid var(--pa-border); }
.detail-grid { display: flex; flex-direction: column; gap: 8px; }
.detail-row { display: flex; gap: 12px; align-items: center; }
.dl { font-size: 12px; color: #9e9890; width: 72px; flex-shrink: 0; }
.dv { font-size: 13px; color: #2c2520; font-weight: 500; }

.archive-item { padding: 10px 0; border-bottom: 1px solid #faf8f5; }
.archive-type { display: flex; align-items: center; gap: 6px; margin-bottom: 4px; }
.archive-icon { font-size: 14px; }
.archive-label { font-size: 11px; font-weight: 700; color: #4a4540; }
.archive-summary { font-size: 12px; color: #2c2520; line-height: 1.5; margin-bottom: 3px; }
.archive-time { font-size: 10px; color: #9e9890; }
</style>
