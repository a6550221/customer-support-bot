<template>
  <div class="settings-view">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">系統設定 Settings</h1>
        <p class="page-sub">Axi 知識庫 · 帳號管理 · 系統配置</p>
      </div>
    </div>

    <div class="settings-layout">
      <!-- Side Nav -->
      <div class="settings-nav">
        <button v-for="s in sections" :key="s.key" :class="['snav-item', activeSection === s.key && 'active']" @click="activeSection = s.key">
          <span class="snav-icon">{{ s.icon }}</span>
          <span>{{ s.label }}</span>
        </button>
      </div>

      <!-- Content -->
      <div class="settings-content">

        <!-- Axi Knowledge Base -->
        <div v-if="activeSection === 'axi'">
          <div class="section-card">
            <div class="section-header">
              <div class="section-title">Axi 知識庫管理 Knowledge Base</div>
              <el-button type="primary" size="small" :icon="Plus" @click="showAddKb = true">新增條目</el-button>
            </div>
            <div class="kb-stats">
              <div class="kb-stat"><div class="kb-stat-val">{{ kbItems.length }}</div><div class="kb-stat-label">知識條目</div></div>
              <div class="kb-stat"><div class="kb-stat-val">{{ kbItems.filter(k=>k.type==='faq').length }}</div><div class="kb-stat-label">FAQ</div></div>
              <div class="kb-stat"><div class="kb-stat-val">{{ kbItems.filter(k=>k.type==='policy').length }}</div><div class="kb-stat-label">政策文件</div></div>
              <div class="kb-stat"><div class="kb-stat-val">98.2%</div><div class="kb-stat-label">準確率</div></div>
            </div>
            <el-input v-model="kbSearch" placeholder="搜尋知識庫..." :prefix-icon="Search" clearable size="small" style="margin-bottom:12px" />
            <div class="kb-list">
              <div v-if="kbLoading" style="text-align:center;padding:20px;color:#9e9890">載入中…</div>
              <div v-for="item in filteredKb" :key="item.id" class="kb-item">
                <div class="kb-item-left">
                  <span :class="['kb-type', item.type]">{{ { faq:'FAQ', policy:'政策', template:'範本', guide:'指引' }[item.type] }}</span>
                  <div class="kb-question">{{ item.question }}</div>
                  <div class="kb-answer">{{ item.answer }}</div>
                </div>
                <div class="kb-item-right">
                  <span class="kb-usage">使用 {{ item.usage }} 次</span>
                  <el-button size="small" text @click="editKbItem(item)">編輯</el-button>
                  <el-button size="small" text type="danger" @click="deleteKbItem(item)">刪除</el-button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Users -->
        <div v-if="activeSection === 'users'">
          <div class="section-card">
            <div class="section-header">
              <div class="section-title">坐席帳號管理 Agent Accounts</div>
              <el-button type="primary" size="small" :icon="Plus" @click="showAddUser = true">新增坐席</el-button>
            </div>
            <div class="user-list">
              <div v-for="u in users" :key="u.id" class="user-row">
                <div class="user-avatar">{{ u.name[0] }}</div>
                <div class="user-info">
                  <div class="user-name">{{ u.name }}</div>
                  <div class="user-email">{{ u.email }}</div>
                </div>
                <span :class="['role-badge', u.role]">{{ { admin:'管理員', agent:'坐席', supervisor:'主管' }[u.role] }}</span>
                <span :class="['status-dot', u.online ? 'online' : 'offline']">{{ u.online ? '在線' : '離線' }}</span>
                <div class="user-stats">{{ u.orders }} 單/月</div>
                <el-switch :model-value="u.active" size="small" @change="toggleUserActive(u)" />
              </div>
            </div>
          </div>
        </div>

        <!-- Notifications -->
        <div v-if="activeSection === 'notifications'">
          <div class="section-card">
            <div class="section-header">
              <div class="section-title">通知設定 Notifications</div>
              <el-button type="primary" size="small" :loading="savingNotifs" @click="saveNotifications">儲存設定</el-button>
            </div>
            <div class="notif-list">
              <div v-for="n in notifications" :key="n.key" class="notif-row">
                <div class="notif-info">
                  <div class="notif-name">{{ n.name }}</div>
                  <div class="notif-desc">{{ n.desc }}</div>
                </div>
                <div class="notif-channels">
                  <el-checkbox v-model="n.email" label="郵件" size="small" />
                  <el-checkbox v-model="n.push" label="推送" size="small" />
                  <el-checkbox v-model="n.sms" label="短信" size="small" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Shifts -->
        <div v-if="activeSection === 'shifts'">
          <div class="section-card">
            <div class="section-header">
              <div class="section-title">班次排班 Shift Management</div>
              <el-button type="primary" size="small" :icon="Plus">新增班次</el-button>
            </div>
            <div class="shift-list">
              <div v-for="s in shifts" :key="s.id" class="shift-row">
                <div class="shift-color" :style="{ background: s.color }" />
                <div class="shift-info">
                  <div class="shift-name">{{ s.name }}</div>
                  <div class="shift-time">{{ s.time }}</div>
                </div>
                <div class="shift-agents">
                  <span v-for="a in s.agents" :key="a" class="shift-agent-tag">{{ a }}</span>
                </div>
                <div class="shift-days">{{ s.days }}</div>
              </div>
            </div>
            <div class="week-calendar">
              <div class="cal-header">
                <span v-for="d in ['週一','週二','週三','週四','週五','週六','週日']" :key="d" class="cal-day-hd">{{ d }}</span>
              </div>
              <div class="cal-row">
                <div v-for="d in 7" :key="d" class="cal-cell">
                  <div class="cal-shift" style="background:#fff3e6;color:#e8851a">早班 陳小明</div>
                  <div v-if="d <= 5" class="cal-shift" style="background:#e6f7ef;color:#2a9d5c">晚班 王大華</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- System -->
        <div v-if="activeSection === 'system'">
          <div class="section-card">
            <div class="section-title" style="margin-bottom:20px">系統配置 System Configuration</div>
            <div class="config-list">
              <div v-for="cfg in systemConfigs" :key="cfg.key" class="config-row">
                <div class="config-info">
                  <div class="config-name">{{ cfg.name }}</div>
                  <div class="config-desc">{{ cfg.desc }}</div>
                </div>
                <component :is="cfg.type === 'switch' ? 'div' : 'div'" class="config-control">
                  <el-switch v-if="cfg.type === 'switch'" v-model="cfg.value" />
                  <el-input v-else-if="cfg.type === 'input'" v-model="cfg.value" size="small" style="width:160px" />
                  <el-select v-else-if="cfg.type === 'select'" v-model="cfg.value" size="small" style="width:130px">
                    <el-option v-for="o in cfg.options" :key="o" :label="o" :value="o" />
                  </el-select>
                </component>
              </div>
            </div>
            <div style="margin-top:20px;text-align:right">
              <el-button type="primary" :loading="savingSystem" @click="saveSystem">儲存設定</el-button>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Add KB Dialog -->
    <el-dialog v-model="showAddKb" title="新增知識庫條目" width="500px">
      <el-form :model="newKb" label-width="80px" size="small">
        <el-form-item label="類型">
          <el-select v-model="newKb.type" style="width:100%">
            <el-option label="FAQ" value="faq" />
            <el-option label="政策" value="policy" />
            <el-option label="範本" value="template" />
            <el-option label="指引" value="guide" />
          </el-select>
        </el-form-item>
        <el-form-item label="問題/標題"><el-input v-model="newKb.question" /></el-form-item>
        <el-form-item label="答案/內容"><el-input v-model="newKb.answer" type="textarea" :rows="4" /></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showAddKb = false">取消</el-button>
        <el-button type="primary" @click="addKbItem">新增</el-button>
      </template>
    </el-dialog>

    <!-- Edit KB Dialog -->
    <el-dialog v-model="showEditKb" title="編輯知識庫條目" width="500px">
      <el-form :model="editKb" label-width="80px" size="small">
        <el-form-item label="類型">
          <el-select v-model="editKb.type" style="width:100%">
            <el-option label="FAQ" value="faq" />
            <el-option label="政策" value="policy" />
            <el-option label="範本" value="template" />
            <el-option label="指引" value="guide" />
          </el-select>
        </el-form-item>
        <el-form-item label="問題/標題"><el-input v-model="editKb.question" /></el-form-item>
        <el-form-item label="答案/內容"><el-input v-model="editKb.answer" type="textarea" :rows="4" /></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showEditKb = false">取消</el-button>
        <el-button type="primary" @click="saveEditKb">儲存</el-button>
      </template>
    </el-dialog>

    <!-- Add User Dialog -->
    <el-dialog v-model="showAddUser" title="新增坐席" width="420px" :close-on-click-modal="false">
      <el-form :model="newUser" label-width="80px" size="small">
        <el-form-item label="姓名"><el-input v-model="newUser.name" placeholder="請輸入姓名" /></el-form-item>
        <el-form-item label="郵箱"><el-input v-model="newUser.email" placeholder="請輸入郵箱" type="email" /></el-form-item>
        <el-form-item label="初始密碼"><el-input v-model="newUser.password" placeholder="至少6位" show-password /></el-form-item>
        <el-form-item label="角色">
          <el-select v-model="newUser.role" style="width:100%">
            <el-option label="坐席" value="agent" />
            <el-option label="主管" value="supervisor" />
            <el-option label="管理員" value="admin" />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showAddUser = false">取消</el-button>
        <el-button type="primary" :loading="addingUser" @click="addUser">建立帳號</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus, Search } from '@element-plus/icons-vue'
import { settingsApi } from '@/api/index.js'

const activeSection = ref('axi')
const sections = [
  { key: 'axi',           label: 'Axi 知識庫', icon: '🤖' },
  { key: 'users',         label: '坐席帳號',   icon: '👥' },
  { key: 'notifications', label: '通知設定',   icon: '🔔' },
  { key: 'shifts',        label: '班次排班',   icon: '📅' },
  { key: 'system',        label: '系統配置',   icon: '⚙️' },
]

// ── Knowledge Base ────────────────────────────────────────────────────────────
const kbSearch  = ref('')
const showAddKb = ref(false)
const showEditKb = ref(false)
const kbItems   = ref([])
const kbLoading = ref(false)

const newKb  = ref({ type: 'faq', question: '', answer: '' })
const editKb = ref({ id: null, type: 'faq', question: '', answer: '' })

const filteredKb = computed(() => {
  if (!kbSearch.value) return kbItems.value
  const q = kbSearch.value.toLowerCase()
  return kbItems.value.filter(k =>
    k.question.toLowerCase().includes(q) || k.answer.toLowerCase().includes(q)
  )
})

async function loadKb() {
  kbLoading.value = true
  try {
    const res = await settingsApi.getKnowledge()
    kbItems.value = (res.data || []).map(k => ({ ...k, usage: k.usage_count ?? 0 }))
  } finally {
    kbLoading.value = false
  }
}

async function addKbItem() {
  if (!newKb.value.question.trim() || !newKb.value.answer.trim()) {
    ElMessage.warning('請填寫問題和答案')
    return
  }
  const res = await settingsApi.createKnowledge(newKb.value)
  kbItems.value.push({ ...res.data, usage: 0 })
  showAddKb.value = false
  newKb.value = { type: 'faq', question: '', answer: '' }
  ElMessage.success('知識庫條目已新增')
}

function editKbItem(item) {
  editKb.value = { id: item.id, type: item.type, question: item.question, answer: item.answer }
  showEditKb.value = true
}

async function saveEditKb() {
  await settingsApi.updateKnowledge(editKb.value.id, {
    type: editKb.value.type,
    question: editKb.value.question,
    answer: editKb.value.answer,
  })
  const idx = kbItems.value.findIndex(k => k.id === editKb.value.id)
  if (idx !== -1) Object.assign(kbItems.value[idx], editKb.value)
  showEditKb.value = false
  ElMessage.success('知識庫條目已更新')
}

async function deleteKbItem(item) {
  await settingsApi.deleteKnowledge(item.id)
  kbItems.value = kbItems.value.filter(k => k.id !== item.id)
  ElMessage.success('已刪除')
}

// ── Users ─────────────────────────────────────────────────────────────────────
const users        = ref([])
const usersLoading = ref(false)
const showAddUser  = ref(false)
const addingUser   = ref(false)
const newUser      = ref({ name: '', email: '', password: '', role: 'agent' })

async function loadUsers() {
  usersLoading.value = true
  try {
    const res = await settingsApi.getUsers()
    users.value = res.data || []
  } finally {
    usersLoading.value = false
  }
}

async function addUser() {
  if (!newUser.value.name.trim() || !newUser.value.email.trim() || !newUser.value.password.trim()) {
    ElMessage.warning('請填寫完整信息')
    return
  }
  addingUser.value = true
  try {
    const res = await settingsApi.createUser(newUser.value)
    users.value.push(res.data)
    showAddUser.value = false
    newUser.value = { name: '', email: '', password: '', role: 'agent' }
    ElMessage.success('坐席帳號已建立')
  } catch (err) {
    const msg = err?.data?.errors?.email?.[0] || err?.message || '建立失敗'
    ElMessage.error(msg)
  } finally {
    addingUser.value = false
  }
}

async function toggleUserActive(user) {
  const newVal = !user.active
  user.active = newVal   // optimistic
  try {
    await settingsApi.updateUser(user.id, { active: newVal })
  } catch {
    user.active = !newVal  // rollback
    ElMessage.error('更新失敗')
  }
}

// ── Settings (system config + notifications) ──────────────────────────────────
const notifications  = ref([])
const systemConfigs  = ref([])
const settingsLoading = ref(false)
const savingSystem    = ref(false)
const savingNotifs    = ref(false)

async function loadSettings() {
  settingsLoading.value = true
  try {
    const res = await settingsApi.getSettings()
    systemConfigs.value  = res.data?.system_configs || []
    notifications.value  = res.data?.notifications  || []
  } finally {
    settingsLoading.value = false
  }
}

async function saveSystem() {
  savingSystem.value = true
  try {
    await settingsApi.saveSettings({ system_configs: systemConfigs.value })
    ElMessage.success('系統設定已儲存')
  } finally {
    savingSystem.value = false
  }
}

async function saveNotifications() {
  savingNotifs.value = true
  try {
    await settingsApi.saveSettings({ notifications: notifications.value })
    ElMessage.success('通知設定已儲存')
  } finally {
    savingNotifs.value = false
  }
}

// ── Shifts (local — no API yet) ───────────────────────────────────────────────
const shifts = ref([
  { id: 1, name: '早班',   time: '08:00 – 17:00', agents: ['陳小明', '林曉月'], days: '週一至週五', color: '#e8851a' },
  { id: 2, name: '下午班', time: '13:00 – 22:00', agents: ['王大華', '趙志遠'], days: '週一至週六', color: '#2a9d5c' },
  { id: 3, name: '夜班',   time: '22:00 – 08:00', agents: ['黃小芬'],           days: '週二、四、六', color: '#3b82f6' },
])

// ── Init ──────────────────────────────────────────────────────────────────────
onMounted(() => {
  loadKb()
  loadUsers()
  loadSettings()
})
</script>

<style scoped>
.settings-view { height: 100%; display: flex; flex-direction: column; padding: 24px; gap: 16px; background: var(--pa-bg); overflow: hidden; }
.page-header  { display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; }
.page-title   { font-size: 20px; font-weight: 700; color: #2c2520; }
.page-sub     { font-size: 12px; color: #9e9890; margin-top: 2px; }

.settings-layout { flex: 1; display: flex; gap: 16px; overflow: hidden; }

/* Side Nav */
.settings-nav { width: 160px; background: #fff; border-radius: 12px; border: 1px solid var(--pa-border); padding: 8px; display: flex; flex-direction: column; gap: 2px; flex-shrink: 0; align-self: flex-start; }
.snav-item {
  display: flex; align-items: center; gap: 8px; padding: 10px 12px; border-radius: 8px;
  border: none; background: transparent; font-size: 13px; color: #706560; cursor: pointer; text-align: left;
  transition: background 0.12s;
}
.snav-item:hover { background: #fdf5ec; color: #2c2520; }
.snav-item.active { background: var(--pa-orange-light); color: var(--pa-orange); font-weight: 700; }
.snav-icon { font-size: 15px; }

/* Content */
.settings-content { flex: 1; overflow-y: auto; }
.section-card { background: #fff; border-radius: 12px; border: 1px solid var(--pa-border); padding: 20px; }
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.section-title { font-size: 14px; font-weight: 700; color: #2c2520; }

/* KB */
.kb-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 16px; }
.kb-stat  { background: #faf8f5; border-radius: 10px; padding: 12px; text-align: center; border: 1px solid var(--pa-border); }
.kb-stat-val   { font-size: 18px; font-weight: 800; color: var(--pa-orange); }
.kb-stat-label { font-size: 10px; color: #9e9890; margin-top: 2px; }
.kb-list { display: flex; flex-direction: column; gap: 8px; max-height: 400px; overflow-y: auto; }
.kb-item { display: flex; align-items: flex-start; gap: 12px; padding: 12px; background: #faf8f5; border-radius: 10px; border: 1px solid var(--pa-border); }
.kb-item-left { flex: 1; }
.kb-type { display: inline-block; font-size: 9px; font-weight: 700; padding: 2px 7px; border-radius: 6px; margin-bottom: 5px; }
.kb-type.faq      { background: #e6f7ef; color: #2a9d5c; }
.kb-type.policy   { background: #fff3e6; color: #e8851a; }
.kb-type.template { background: #ede9ff; color: #7c3aed; }
.kb-type.guide    { background: #e0f2fe; color: #0284c7; }
.kb-question { font-size: 13px; font-weight: 700; color: #2c2520; margin-bottom: 4px; }
.kb-answer   { font-size: 11px; color: #706560; line-height: 1.5; }
.kb-item-right { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; flex-shrink: 0; }
.kb-usage { font-size: 10px; color: #9e9890; }

/* Users */
.user-list { display: flex; flex-direction: column; gap: 8px; }
.user-row { display: flex; align-items: center; gap: 12px; padding: 12px 14px; background: #faf8f5; border-radius: 10px; border: 1px solid var(--pa-border); }
.user-avatar { width: 32px; height: 32px; border-radius: 50%; background: var(--pa-orange); color: #fff; font-size: 13px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.user-info { flex: 1; }
.user-name  { font-size: 13px; font-weight: 700; color: #2c2520; }
.user-email { font-size: 11px; color: #9e9890; }
.role-badge { font-size: 10px; padding: 2px 8px; border-radius: 8px; font-weight: 700; }
.role-badge.admin      { background: #fdeee8; color: #d44e2a; }
.role-badge.supervisor { background: #fff3e6; color: #e8851a; }
.role-badge.agent      { background: #e6f7ef; color: #2a9d5c; }
.status-dot { font-size: 10px; font-weight: 700; }
.status-dot.online  { color: #2a9d5c; }
.status-dot.offline { color: #9e9890; }
.user-stats { font-size: 11px; color: #706560; min-width: 60px; text-align: right; }

/* Notifications */
.notif-list { display: flex; flex-direction: column; gap: 0; }
.notif-row { display: flex; align-items: center; justify-content: space-between; padding: 14px 0; border-bottom: 1px solid #faf8f5; }
.notif-row:last-child { border-bottom: none; }
.notif-info { flex: 1; }
.notif-name { font-size: 13px; font-weight: 700; color: #2c2520; margin-bottom: 2px; }
.notif-desc { font-size: 11px; color: #9e9890; }
.notif-channels { display: flex; gap: 12px; }

/* Shifts */
.shift-list { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }
.shift-row  { display: flex; align-items: center; gap: 12px; padding: 12px 14px; background: #faf8f5; border-radius: 10px; border: 1px solid var(--pa-border); }
.shift-color { width: 12px; height: 36px; border-radius: 4px; flex-shrink: 0; }
.shift-info { flex: 1; }
.shift-name { font-size: 13px; font-weight: 700; color: #2c2520; }
.shift-time { font-size: 11px; color: #9e9890; }
.shift-agents { display: flex; gap: 5px; flex-wrap: wrap; }
.shift-agent-tag { background: #fff; border: 1px solid var(--pa-border); border-radius: 6px; font-size: 11px; padding: 2px 8px; color: #4a4540; }
.shift-days { font-size: 11px; color: #706560; min-width: 100px; text-align: right; }
.week-calendar { border: 1px solid var(--pa-border); border-radius: 10px; overflow: hidden; }
.cal-header { display: grid; grid-template-columns: repeat(7, 1fr); background: #faf8f5; border-bottom: 1px solid var(--pa-border); }
.cal-day-hd { padding: 8px; text-align: center; font-size: 11px; font-weight: 700; color: #706560; }
.cal-row  { display: grid; grid-template-columns: repeat(7, 1fr); }
.cal-cell { padding: 8px; border-right: 1px solid #faf8f5; display: flex; flex-direction: column; gap: 3px; min-height: 60px; }
.cal-shift { font-size: 10px; padding: 3px 6px; border-radius: 5px; font-weight: 600; }

/* System Config */
.config-list { display: flex; flex-direction: column; gap: 0; }
.config-row  { display: flex; align-items: center; justify-content: space-between; padding: 14px 0; border-bottom: 1px solid #faf8f5; }
.config-row:last-child { border-bottom: none; }
.config-info { flex: 1; }
.config-name { font-size: 13px; font-weight: 700; color: #2c2520; margin-bottom: 2px; }
.config-desc { font-size: 11px; color: #9e9890; }
</style>
