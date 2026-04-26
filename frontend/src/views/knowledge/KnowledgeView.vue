<template>
  <div class="knowledge-page">
    <div class="page-header">
      <h2>知識庫</h2>
      <div class="header-actions">
        <el-input v-model="search" placeholder="搜尋文章..." clearable prefix-icon="Search" style="width: 240px" @input="onSearch" />
        <el-button type="primary" icon="Plus" @click="openEditor(null)">新增文章</el-button>
      </div>
    </div>

    <el-row :gutter="16">
      <el-col :span="6">
        <el-card shadow="never" class="category-card">
          <div class="cat-title">分類篩選</div>
          <el-menu v-model:default-active="activeCategory" @select="onCategorySelect">
            <el-menu-item index="">全部文章</el-menu-item>
            <el-menu-item v-for="c in categories" :key="c.id" :index="String(c.id)">{{ c.name }}</el-menu-item>
          </el-menu>
        </el-card>
      </el-col>

      <el-col :span="18">
        <el-card shadow="never">
          <el-table :data="articles" v-loading="loading" stripe @row-click="viewArticle">
            <el-table-column label="標題" prop="title" min-width="240" show-overflow-tooltip />
            <el-table-column label="分類" width="120">
              <template #default="{ row }">{{ row.category?.name || '—' }}</template>
            </el-table-column>
            <el-table-column label="狀態" width="100">
              <template #default="{ row }">
                <el-tag :type="row.status === 'published' ? 'success' : 'info'" size="small">
                  {{ row.status === 'published' ? '已發布' : '草稿' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="瀏覽" prop="views" width="80" />
            <el-table-column label="操作" width="120">
              <template #default="{ row }">
                <el-button size="small" text icon="Edit" @click.stop="openEditor(row)">編輯</el-button>
                <el-button size="small" text type="danger" icon="Delete" @click.stop="deleteArticle(row)">刪除</el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </el-col>
    </el-row>

    <!-- Editor dialog -->
    <el-dialog v-model="showEditor" :title="editForm.id ? '編輯文章' : '新增文章'" width="700px">
      <el-form :model="editForm" label-position="top">
        <el-form-item label="標題">
          <el-input v-model="editForm.title" placeholder="文章標題" />
        </el-form-item>
        <el-row :gutter="12">
          <el-col :span="12">
            <el-form-item label="分類">
              <el-select v-model="editForm.category_id" clearable style="width: 100%">
                <el-option v-for="c in categories" :key="c.id" :label="c.name" :value="c.id" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="狀態">
              <el-select v-model="editForm.status" style="width: 100%">
                <el-option label="已發布" value="published" />
                <el-option label="草稿" value="draft" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="內容">
          <el-input v-model="editForm.content" type="textarea" :rows="12" placeholder="文章內容（支援 HTML）" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showEditor = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="saveArticle">儲存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { knowledgeApi, categoryApi } from '@/api'
import { useDebounceFn } from '@vueuse/core'

const router         = useRouter()
const articles       = ref([])
const categories     = ref([])
const loading        = ref(false)
const showEditor     = ref(false)
const saving         = ref(false)
const search         = ref('')
const activeCategory = ref('')

const editForm = reactive({ id: null, title: '', content: '', category_id: null, status: 'published' })

async function loadArticles(params = {}) {
  loading.value = true
  try {
    const res  = await knowledgeApi.list(params)
    articles.value = res.data.data
  } finally {
    loading.value = false
  }
}

const onSearch = useDebounceFn(() => loadArticles({ search: search.value }), 400)

function onCategorySelect(id) {
  loadArticles(id ? { category_id: id } : {})
}

function viewArticle(row) {
  router.push(`/knowledge/${row.id}`)
}

function openEditor(row) {
  if (row) {
    Object.assign(editForm, { id: row.id, title: row.title, content: row.content, category_id: row.category_id, status: row.status })
  } else {
    Object.assign(editForm, { id: null, title: '', content: '', category_id: null, status: 'published' })
  }
  showEditor.value = true
}

async function saveArticle() {
  saving.value = true
  try {
    if (editForm.id) {
      await knowledgeApi.update(editForm.id, editForm)
    } else {
      await knowledgeApi.create(editForm)
    }
    ElMessage.success('儲存成功')
    showEditor.value = false
    loadArticles()
  } finally {
    saving.value = false
  }
}

async function deleteArticle(row) {
  await ElMessageBox.confirm(`確定刪除「${row.title}」？`, '確認刪除', { type: 'warning' })
  await knowledgeApi.delete(row.id)
  ElMessage.success('已刪除')
  loadArticles()
}

onMounted(async () => {
  loadArticles()
  const res = await categoryApi.list()
  categories.value = res.data
})
</script>

<style scoped>
.knowledge-page { padding: 24px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-header h2 { font-size: 20px; font-weight: 600; }
.header-actions { display: flex; gap: 8px; }
.category-card { }
.cat-title { font-weight: 600; font-size: 13px; color: #64748b; padding: 0 0 12px; }
</style>
