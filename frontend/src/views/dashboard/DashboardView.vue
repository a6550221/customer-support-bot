<template>
  <div class="dashboard-page">
    <div class="page-header">
      <h2>{{ t('dashboard.title') }}</h2>
      <el-radio-group v-model="period" @change="loadData">
        <el-radio-button :value="1">今日</el-radio-button>
        <el-radio-button :value="7">7 日</el-radio-button>
        <el-radio-button :value="30">30 日</el-radio-button>
      </el-radio-group>
    </div>

    <!-- Stats cards -->
    <el-row :gutter="16" class="stats-row">
      <el-col :span="6" v-for="card in statCards" :key="card.key">
        <el-card class="stat-card" shadow="never">
          <div class="stat-icon" :style="{ background: card.color }">
            <el-icon size="22" color="#fff"><component :is="card.icon" /></el-icon>
          </div>
          <div class="stat-value">{{ stats[card.key] ?? '—' }}</div>
          <div class="stat-label">{{ card.label }}</div>
        </el-card>
      </el-col>
    </el-row>

    <el-row :gutter="16" class="chart-row">
      <!-- Trend chart -->
      <el-col :span="16">
        <el-card shadow="never">
          <template #header>
            <span class="card-title">工單趨勢（近 {{ period }} 日）</span>
          </template>
          <div ref="trendChartRef" style="height: 260px" />
        </el-card>
      </el-col>

      <!-- CSAT -->
      <el-col :span="8">
        <el-card shadow="never">
          <template #header><span class="card-title">CSAT 滿意度</span></template>
          <div class="csat-center">
            <div class="csat-score">{{ csat.average || '—' }}</div>
            <el-rate :model-value="parseFloat(csat.average) || 0" disabled show-score />
            <div class="csat-total">共 {{ csat.total_rated || 0 }} 則評分</div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- Agent status -->
    <el-card shadow="never" class="agents-card">
      <template #header><span class="card-title">坐席狀態總覽</span></template>
      <el-table :data="agents" stripe>
        <el-table-column label="坐席" prop="name" />
        <el-table-column label="狀態" width="100">
          <template #default="{ row }">
            <el-tag :type="{ online: 'success', busy: 'warning', offline: 'info' }[row.status]" size="small">
              {{ { online: '在線', busy: '忙碌', offline: '離線' }[row.status] }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="處理中工單" prop="active_tickets" width="120" />
        <el-table-column label="今日解決" prop="resolved_today" width="100" />
      </el-table>
    </el-card>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import { dashboardApi } from '@/api'
import * as echarts from 'echarts/core'
import { LineChart } from 'echarts/charts'
import { GridComponent, TooltipComponent, LegendComponent } from 'echarts/components'
import { CanvasRenderer } from 'echarts/renderers'
echarts.use([LineChart, GridComponent, TooltipComponent, LegendComponent, CanvasRenderer])

const { t } = useI18n()
const period        = ref(7)
const stats         = ref({})
const csat          = ref({})
const agents        = ref([])
const trendChartRef = ref()
let chart           = null

const statCards = [
  { key: 'today_tickets',    label: '今日工單',     icon: 'Tickets',     color: '#4F46E5' },
  { key: 'pending_tickets',  label: '待處理',       icon: 'Clock',       color: '#f97316' },
  { key: 'resolved_today',   label: '今日解決',     icon: 'CircleCheck', color: '#22c55e' },
  { key: 'avg_first_response', label: '平均首次回應(秒)', icon: 'Timer', color: '#0ea5e9' },
]

async function loadData() {
  const [s, trend, a, c] = await Promise.all([
    dashboardApi.stats(),
    dashboardApi.trend(period.value),
    dashboardApi.agents(),
    dashboardApi.csat(),
  ])
  stats.value  = s.data
  agents.value = a.data
  csat.value   = c.data

  await nextTick()
  renderChart(trend.data)
}

function renderChart(data) {
  if (!trendChartRef.value) return
  if (!chart) chart = echarts.init(trendChartRef.value)
  chart.setOption({
    tooltip: { trigger: 'axis' },
    xAxis:   { type: 'category', data: data.map(d => d.date) },
    yAxis:   { type: 'value', minInterval: 1 },
    series:  [{
      name: '工單數',
      type: 'line',
      data: data.map(d => d.count),
      smooth: true,
      areaStyle: { color: 'rgba(79,70,229,0.1)' },
      lineStyle: { color: '#4F46E5' },
      itemStyle: { color: '#4F46E5' },
    }],
    grid: { left: '3%', right: '3%', bottom: '3%', containLabel: true },
  })
}

onMounted(loadData)
</script>

<style scoped>
.dashboard-page { padding: 24px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-header h2 { font-size: 20px; font-weight: 600; }
.stats-row { margin-bottom: 16px; }
.stat-card { border-radius: 12px; display: flex; flex-direction: column; align-items: center; padding: 8px 0; }
.stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 12px; }
.stat-value { font-size: 28px; font-weight: 700; color: #1e293b; }
.stat-label { font-size: 13px; color: #64748b; margin-top: 4px; }
.chart-row { margin-bottom: 16px; }
.card-title { font-weight: 600; font-size: 14px; }
.csat-center { display: flex; flex-direction: column; align-items: center; padding: 20px 0; gap: 12px; }
.csat-score { font-size: 48px; font-weight: 700; color: #4F46E5; }
.csat-total { color: #94a3b8; font-size: 13px; }
.agents-card { }
</style>
