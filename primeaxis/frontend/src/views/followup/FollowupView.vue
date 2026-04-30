<template>
  <div class="followup-view">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">每日跟進 Daily Follow-up</h1>
        <p class="page-sub">{{ today }} · {{ pending }} 項待辦，{{ done }} 項已完成</p>
      </div>
      <div class="header-actions">
        <el-button :icon="Plus" type="primary" @click="showAdd = true">新增跟進</el-button>
      </div>
    </div>

    <!-- Progress Bar -->
    <div class="progress-card">
      <div class="progress-label">
        <span>今日完成進度</span>
        <span class="progress-pct">{{ progressPct }}%</span>
      </div>
      <div class="progress-track">
        <div class="progress-fill" :style="{ width: progressPct + '%' }" />
      </div>
      <div class="progress-stats">
        <span class="stat green">✓ {{ done }} 已完成</span>
        <span class="stat orange">◑ {{ inProgress }} 進行中</span>
        <span class="stat gray">○ {{ pending }} 待辦</span>
      </div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        :class="['tab-btn', activeTab === tab.key && 'active']"
        @click="activeTab = tab.key"
      >{{ tab.label }}<span v-if="tab.count" class="tab-count">{{ tab.count }}</span></button>
    </div>

    <!-- Task List -->
    <div class="task-list">
      <div
        v-for="task in filteredTasks"
        :key="task.id"
        :class="['task-card', 'priority-' + task.priority, task.status === 'done' && 'is-done']"
      >
        <div class="task-check" @click="toggleDone(task)">
          <div :class="['check-circle', task.status === 'done' && 'checked']">
            <span v-if="task.status === 'done'">✓</span>
          </div>
        </div>
        <div class="task-body">
          <div class="task-title">{{ task.title }}</div>
          <div class="task-meta">
            <span class="task-order">{{ task.order_no }}</span>
            <span class="task-customer">{{ task.customer }}</span>
            <span :class="['task-priority', 'p-' + task.priority]">{{ { high:'緊急', medium:'一般', low:'低優先' }[task.priority] }}</span>
          </div>
          <div class="task-note" v-if="task.note">{{ task.note }}</div>
        </div>
        <div class="task-right">
          <div class="task-time">{{ task.dueTime }}</div>
          <div :class="['task-status', task.status]">
            {{ { todo:'待辦', inprogress:'進行中', done:'已完成' }[task.status] }}
          </div>
          <div class="task-assignee">{{ task.assignee }}</div>
        </div>
        <div class="task-actions">
          <el-select
            :model-value="task.status"
            size="small"
            style="width:90px"
            @change="v => changeStatus(task, v)"
          >
            <el-option label="待辦" value="todo" />
            <el-option label="進行中" value="inprogress" />
            <el-option label="已完成" value="done" />
          </el-select>
        </div>
      </div>

      <div v-if="filteredTasks.length === 0" class="empty-state">
        <div class="empty-icon">📋</div>
        <div>暫無跟進項目</div>
      </div>
    </div>

    <!-- Add Task Dialog -->
    <el-dialog v-model="showAdd" title="新增跟進任務" width="460px">
      <el-form :model="newTask" label-width="80px" size="small">
        <el-form-item label="任務標題">
          <el-input v-model="newTask.title" placeholder="輸入跟進說明..." />
        </el-form-item>
        <el-form-item label="關聯訂單">
          <el-input v-model="newTask.order_no" placeholder="PA-2024-XXXX" />
        </el-form-item>
        <el-form-item label="客戶名稱">
          <el-input v-model="newTask.customer" />
        </el-form-item>
        <el-form-item label="優先級">
          <el-select v-model="newTask.priority" style="width:100%">
            <el-option label="緊急" value="high" />
            <el-option label="一般" value="medium" />
            <el-option label="低優先" value="low" />
          </el-select>
        </el-form-item>
        <el-form-item label="截止時間">
          <el-input v-model="newTask.dueTime" placeholder="如：14:00" />
        </el-form-item>
        <el-form-item label="備注">
          <el-input v-model="newTask.note" type="textarea" :rows="2" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showAdd = false">取消</el-button>
        <el-button type="primary" @click="addTask">新增</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import { followupApi } from '@/api/index.js'
import dayjs from 'dayjs'

const today   = dayjs().format('YYYY年MM月DD日')
const showAdd = ref(false)
const activeTab = ref('all')
const loading = ref(false)

const tasks = ref([])

async function loadTasks() {
  loading.value = true
  try {
    const res = await followupApi.list()
    tasks.value = (res.data || []).map(t => ({
      ...t,
      dueTime:  t.due_time ?? '',
      assignee: t.assignee_name ?? '',
    }))
  } finally {
    loading.value = false
  }
}

const tabs = computed(() => [
  { key: 'all',        label: '全部',   count: tasks.value.length },
  { key: 'todo',       label: '待辦',   count: tasks.value.filter(t => t.status === 'todo').length },
  { key: 'inprogress', label: '進行中', count: tasks.value.filter(t => t.status === 'inprogress').length },
  { key: 'done',       label: '已完成', count: tasks.value.filter(t => t.status === 'done').length },
])

const filteredTasks = computed(() => {
  if (activeTab.value === 'all') return tasks.value
  return tasks.value.filter(t => t.status === activeTab.value)
})

const done        = computed(() => tasks.value.filter(t => t.status === 'done').length)
const inProgress  = computed(() => tasks.value.filter(t => t.status === 'inprogress').length)
const pending     = computed(() => tasks.value.filter(t => t.status === 'todo').length)
const progressPct = computed(() => tasks.value.length ? Math.round((done.value / tasks.value.length) * 100) : 0)

async function toggleDone(task) {
  const newStatus = task.status === 'done' ? 'todo' : 'done'
  task.status = newStatus  // optimistic
  try {
    await followupApi.update(task.id, { status: newStatus })
  } catch {
    task.status = newStatus === 'done' ? 'todo' : 'done'  // rollback
  }
}

async function changeStatus(task, val) {
  const prev = task.status
  task.status = val  // optimistic
  try {
    await followupApi.update(task.id, { status: val })
    ElMessage.success('狀態已更新')
  } catch {
    task.status = prev
  }
}

const newTask = ref({ title: '', order_no: '', customer: '', priority: 'medium', dueTime: '', note: '' })

async function addTask() {
  if (!newTask.value.title.trim()) {
    ElMessage.warning('請填寫任務標題')
    return
  }
  const res = await followupApi.create({
    title:    newTask.value.title,
    order_no: newTask.value.order_no,
    customer: newTask.value.customer,
    priority: newTask.value.priority,
    due_time: newTask.value.dueTime,
    note:     newTask.value.note,
  })
  const t = res.data
  tasks.value.unshift({ ...t, dueTime: t.due_time ?? '', assignee: t.assignee_name ?? '' })
  showAdd.value = false
  newTask.value = { title: '', order_no: '', customer: '', priority: 'medium', dueTime: '', note: '' }
  ElMessage.success('跟進任務已新增')
}

onMounted(loadTasks)
</script>

<style scoped>
.followup-view { height: 100%; display: flex; flex-direction: column; padding: 24px; gap: 16px; background: var(--pa-bg); overflow: hidden; }
.page-header { display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; }
.page-title  { font-size: 20px; font-weight: 700; color: #2c2520; }
.page-sub    { font-size: 12px; color: #9e9890; margin-top: 2px; }

/* Progress */
.progress-card {
  background: #fff; border-radius: 12px; padding: 16px 20px;
  border: 1px solid var(--pa-border); flex-shrink: 0;
}
.progress-label { display: flex; justify-content: space-between; font-size: 13px; font-weight: 600; color: #2c2520; margin-bottom: 8px; }
.progress-pct  { color: var(--pa-orange); font-size: 15px; font-weight: 800; }
.progress-track { height: 8px; background: #f0ece6; border-radius: 4px; overflow: hidden; margin-bottom: 10px; }
.progress-fill  { height: 100%; background: linear-gradient(90deg, var(--pa-orange), #f0a843); border-radius: 4px; transition: width 0.4s; }
.progress-stats { display: flex; gap: 16px; }
.stat { font-size: 12px; font-weight: 600; }
.stat.green  { color: #2a9d5c; }
.stat.orange { color: var(--pa-orange); }
.stat.gray   { color: #9e9890; }

/* Tabs */
.filter-tabs { display: flex; gap: 4px; flex-shrink: 0; }
.tab-btn {
  padding: 7px 16px; border-radius: 8px; border: 1.5px solid transparent;
  background: #fff; font-size: 12px; font-weight: 600; color: #9e9890; cursor: pointer;
  display: flex; align-items: center; gap: 5px; transition: all 0.15s;
}
.tab-btn:hover { background: #fdf5ec; color: #2c2520; }
.tab-btn.active { background: var(--pa-orange); color: #fff; border-color: var(--pa-orange); }
.tab-count { background: rgba(255,255,255,0.3); border-radius: 8px; padding: 0 6px; font-size: 10px; min-width: 18px; text-align: center; }
.tab-btn:not(.active) .tab-count { background: #f0ece6; color: #9e9890; }

/* Task List */
.task-list { flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; }
.task-card {
  background: #fff; border-radius: 12px; border: 1px solid var(--pa-border);
  padding: 14px 16px; display: flex; align-items: flex-start; gap: 12px;
  border-left: 4px solid var(--pa-border); transition: box-shadow 0.15s;
}
.task-card:hover { box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
.task-card.priority-high   { border-left-color: #d44e2a; }
.task-card.priority-medium { border-left-color: var(--pa-orange); }
.task-card.priority-low    { border-left-color: #c9a227; }
.task-card.is-done { opacity: 0.6; }

.task-check { flex-shrink: 0; padding-top: 2px; }
.check-circle {
  width: 20px; height: 20px; border-radius: 50%; border: 2px solid #d4cfc8;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; font-size: 11px; color: #fff; transition: all 0.15s;
}
.check-circle.checked { background: #2a9d5c; border-color: #2a9d5c; }
.check-circle:hover { border-color: #2a9d5c; }

.task-body { flex: 1; min-width: 0; }
.task-title { font-size: 13px; font-weight: 600; color: #2c2520; margin-bottom: 5px; }
.is-done .task-title { text-decoration: line-through; color: #9e9890; }
.task-meta { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
.task-order    { font-family: monospace; font-size: 11px; font-weight: 700; color: var(--pa-orange); }
.task-customer { font-size: 11px; color: #706560; }
.task-priority { font-size: 10px; padding: 1px 7px; border-radius: 8px; font-weight: 700; }
.p-high   { background: #fdeee8; color: #d44e2a; }
.p-medium { background: #fff3e6; color: #e8851a; }
.p-low    { background: #fdf5e0; color: #c9a227; }
.task-note { font-size: 11px; color: #9e9890; line-height: 1.4; }

.task-right { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; min-width: 80px; }
.task-time { font-size: 12px; font-weight: 700; color: #2c2520; }
.task-status { font-size: 10px; padding: 2px 8px; border-radius: 8px; font-weight: 600; }
.task-status.todo       { background: #f0ece6; color: #706560; }
.task-status.inprogress { background: #fff3e6; color: #e8851a; }
.task-status.done       { background: #e6f7ef; color: #2a9d5c; }
.task-assignee { font-size: 10px; color: #9e9890; }

.task-actions { display: flex; align-items: center; }

.empty-state { padding: 48px; text-align: center; color: #9e9890; font-size: 13px; }
.empty-icon  { font-size: 36px; margin-bottom: 8px; opacity: 0.4; }
</style>
