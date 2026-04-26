<template>
  <div class="article-page" v-loading="loading">
    <template v-if="article">
      <div class="article-header">
        <el-button icon="ArrowLeft" @click="$router.back()" />
        <h2>{{ article.title }}</h2>
        <div class="article-meta">
          <el-tag :type="article.status === 'published' ? 'success' : 'info'" size="small">
            {{ article.status === 'published' ? '已發布' : '草稿' }}
          </el-tag>
          <span class="meta-text">{{ article.category?.name }}</span>
          <span class="meta-text">瀏覽 {{ article.views }} 次</span>
          <span class="meta-text">{{ article.author?.name }}</span>
        </div>
      </div>
      <el-card shadow="never" class="article-body">
        <div v-html="article.content" class="article-content" />
      </el-card>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { knowledgeApi } from '@/api'

const route   = useRoute()
const article = ref(null)
const loading = ref(false)

onMounted(async () => {
  loading.value = true
  try {
    const res = await knowledgeApi.get(route.params.id)
    article.value = res.data
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.article-page { padding: 24px; }
.article-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
.article-header h2 { font-size: 22px; font-weight: 700; flex: 1; }
.article-meta { display: flex; align-items: center; gap: 10px; width: 100%; padding-left: 44px; }
.meta-text { font-size: 13px; color: #94a3b8; }
.article-body { border-radius: 12px; }
.article-content { line-height: 1.8; font-size: 15px; color: #374151; }
.article-content :deep(h1, h2, h3) { margin: 1em 0 0.5em; }
.article-content :deep(p) { margin-bottom: 0.8em; }
</style>
