<template>
  <div class="settings-page">
    <h2>系統設定</h2>
    <el-tabs v-model="activeTab">
      <!-- Users -->
      <el-tab-pane label="用戶管理" name="users">
        <div class="tab-header">
          <el-button type="primary" icon="Plus" @click="openUserDialog(null)">新增用戶</el-button>
        </div>
        <el-table :data="users" v-loading="loading" stripe>
          <el-table-column label="姓名" prop="name" />
          <el-table-column label="Email" prop="email" />
          <el-table-column label="角色" width="100">
            <template #default="{ row }">
              <el-tag :type="{ admin: 'danger', supervisor: 'warning', agent: '' }[row.role]" size="small">{{ row.role }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column label="狀態" width="90">
            <template #default="{ row }">
              <el-tag :type="{ online: 'success', busy: 'warning', offline: 'info' }[row.status]" size="small">{{ row.status }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column label="部門" prop="department" />
          <el-table-column label="操作" width="140">
            <template #default="{ row }">
              <el-button size="small" text icon="Edit" @click="openUserDialog(row)">編輯</el-button>
              <el-button size="small" text type="danger" icon="Delete" @click="deleteUser(row)">刪除</el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-tab-pane>

      <!-- Categories -->
      <el-tab-pane label="分類管理" name="categories">
        <div class="tab-header">
          <el-button type="primary" icon="Plus" @click="openCatDialog(null)">新增分類</el-button>
        </div>
        <el-table :data="categories" stripe>
          <el-table-column label="分類名稱" prop="name" />
          <el-table-column label="操作" width="140">
            <template #default="{ row }">
              <el-button size="small" text icon="Edit" @click="openCatDialog(row)">編輯</el-button>
              <el-button size="small" text type="danger" icon="Delete" @click="deleteCat(row)">刪除</el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-tab-pane>

      <!-- Quick Replies -->
      <el-tab-pane label="快捷回覆" name="quick_replies">
        <div class="tab-header">
          <el-button type="primary" icon="Plus" @click="openQrDialog(null)">新增快捷回覆</el-button>
        </div>
        <el-table :data="quickReplies" stripe>
          <el-table-column label="標題" prop="title" width="200" />
          <el-table-column label="內容" prop="content" show-overflow-tooltip />
          <el-table-column label="全局" width="80">
            <template #default="{ row }">
              <el-tag v-if="row.is_global" type="success" size="small">全局</el-tag>
            </template>
          </el-table-column>
          <el-table-column label="操作" width="140">
            <template #default="{ row }">
              <el-button size="small" text icon="Edit" @click="openQrDialog(row)">編輯</el-button>
              <el-button size="small" text type="danger" icon="Delete" @click="deleteQr(row)">刪除</el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-tab-pane>
    </el-tabs>

    <!-- User dialog -->
    <el-dialog v-model="showUserDlg" :title="userForm.id ? '編輯用戶' : '新增用戶'" width="480px">
      <el-form :model="userForm" label-position="top">
        <el-row :gutter="12">
          <el-col :span="12"><el-form-item label="姓名"><el-input v-model="userForm.name" /></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="Email"><el-input v-model="userForm.email" /></el-form-item></el-col>
        </el-row>
        <el-row :gutter="12">
          <el-col :span="12">
            <el-form-item label="角色">
              <el-select v-model="userForm.role" style="width: 100%">
                <el-option label="Admin" value="admin" />
                <el-option label="Supervisor" value="supervisor" />
                <el-option label="Agent" value="agent" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12"><el-form-item label="部門"><el-input v-model="userForm.department" /></el-form-item></el-col>
        </el-row>
        <el-form-item :label="userForm.id ? '新密碼（留空不修改）' : '密碼'">
          <el-input v-model="userForm.password" type="password" show-password />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showUserDlg = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="saveUser">儲存</el-button>
      </template>
    </el-dialog>

    <!-- Category dialog -->
    <el-dialog v-model="showCatDlg" :title="catForm.id ? '編輯分類' : '新增分類'" width="380px">
      <el-form :model="catForm" label-position="top">
        <el-form-item label="分類名稱"><el-input v-model="catForm.name" /></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showCatDlg = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="saveCat">儲存</el-button>
      </template>
    </el-dialog>

    <!-- Quick Reply dialog -->
    <el-dialog v-model="showQrDlg" :title="qrForm.id ? '編輯快捷回覆' : '新增快捷回覆'" width="480px">
      <el-form :model="qrForm" label-position="top">
        <el-form-item label="標題"><el-input v-model="qrForm.title" /></el-form-item>
        <el-form-item label="內容"><el-input v-model="qrForm.content" type="textarea" :rows="4" /></el-form-item>
        <el-form-item label="全局（所有坐席可用）"><el-switch v-model="qrForm.is_global" /></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showQrDlg = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="saveQr">儲存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { userApi, categoryApi, quickReplyApi } from '@/api'

const activeTab  = ref('users')
const users      = ref([])
const categories = ref([])
const quickReplies = ref([])
const loading    = ref(false)
const saving     = ref(false)

const showUserDlg = ref(false)
const showCatDlg  = ref(false)
const showQrDlg   = ref(false)

const userForm = reactive({ id: null, name: '', email: '', role: 'agent', department: '', password: '' })
const catForm  = reactive({ id: null, name: '' })
const qrForm   = reactive({ id: null, title: '', content: '', is_global: false })

async function loadAll() {
  loading.value = true
  const [u, c, q] = await Promise.all([userApi.list(), categoryApi.list(), quickReplyApi.list()])
  users.value        = u.data
  categories.value   = c.data
  quickReplies.value = q.data
  loading.value      = false
}

function openUserDialog(row) {
  Object.assign(userForm, row ? { ...row, password: '' } : { id: null, name: '', email: '', role: 'agent', department: '', password: '' })
  showUserDlg.value = true
}
function openCatDialog(row) {
  Object.assign(catForm, row ? { ...row } : { id: null, name: '' })
  showCatDlg.value = true
}
function openQrDialog(row) {
  Object.assign(qrForm, row ? { ...row } : { id: null, title: '', content: '', is_global: false })
  showQrDlg.value = true
}

async function saveUser() {
  saving.value = true
  try {
    userForm.id ? await userApi.update(userForm.id, userForm) : await userApi.create(userForm)
    ElMessage.success('儲存成功'); showUserDlg.value = false; loadAll()
  } finally { saving.value = false }
}
async function saveCat() {
  saving.value = true
  try {
    catForm.id ? await categoryApi.update(catForm.id, catForm) : await categoryApi.create(catForm)
    ElMessage.success('儲存成功'); showCatDlg.value = false; loadAll()
  } finally { saving.value = false }
}
async function saveQr() {
  saving.value = true
  try {
    qrForm.id ? await quickReplyApi.update(qrForm.id, qrForm) : await quickReplyApi.create(qrForm)
    ElMessage.success('儲存成功'); showQrDlg.value = false; loadAll()
  } finally { saving.value = false }
}

async function deleteUser(row) {
  await ElMessageBox.confirm(`確定刪除用戶 ${row.name}？`, '確認', { type: 'warning' })
  await userApi.delete(row.id); ElMessage.success('已刪除'); loadAll()
}
async function deleteCat(row) {
  await ElMessageBox.confirm(`確定刪除分類 ${row.name}？`, '確認', { type: 'warning' })
  await categoryApi.delete(row.id); ElMessage.success('已刪除'); loadAll()
}
async function deleteQr(row) {
  await ElMessageBox.confirm(`確定刪除「${row.title}」？`, '確認', { type: 'warning' })
  await quickReplyApi.delete(row.id); ElMessage.success('已刪除'); loadAll()
}

onMounted(loadAll)
</script>

<style scoped>
.settings-page { padding: 24px; }
.settings-page h2 { font-size: 20px; font-weight: 600; margin-bottom: 20px; }
.tab-header { margin-bottom: 16px; }
</style>
