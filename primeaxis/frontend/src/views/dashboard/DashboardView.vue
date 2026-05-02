<template>
  <div class="dashboard">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">儀表板 Dashboard</h1>
        <p class="page-sub">{{ today }} · 今日數據概覽</p>
      </div>
      <el-button :icon="Refresh" circle size="small" @click="init" :loading="loading" />
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
      <div v-for="k in kpis" :key="k.label" class="kpi-card">
        <div class="kpi-icon" :style="{ background: k.iconBg, color: k.iconColor }">
          <el-icon><component :is="k.icon" /></el-icon>
        </div>
        <div>
          <div class="kpi-value">{{ k.value }}</div>
          <div class="kpi-label">{{ k.label }}</div>
        </div>
        <div class="kpi-change" :class="k.trend > 0 ? 'up' : 'down'">
          {{ k.trend > 0 ? '↑' : '↓' }} {{ Math.abs(k.trend) }}%
        </div>
      </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="charts-row">
      <!-- Logistics Trend Line Chart -->
      <div class="chart-card wide">
        <div class="chart-header">
          <span class="chart-title">物流案件趨勢 Logistics Trend</span>
          <el-select v-model="trendDays" size="small" style="width:100px" @change="init">
            <el-option label="7天" :value="7" />
            <el-option label="30天" :value="30" />
            <el-option label="90天" :value="90" />
          </el-select>
        </div>
        <v-chart class="chart" :option="trendOption" autoresize />
      </div>

      <!-- Order Status Donut -->
      <div class="chart-card">
        <div class="chart-header">
          <span class="chart-title">訂單狀態分佈</span>
        </div>
        <v-chart class="chart" :option="donutOption" autoresize />
      </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="charts-row">
      <!-- Revenue/Expense Bar -->
      <div class="chart-card">
        <div class="chart-header">
          <span class="chart-title">收入支出趨勢 Revenue</span>
        </div>
        <v-chart class="chart" :option="revenueOption" autoresize />
      </div>

      <!-- 7-day Status Stacked Bar -->
      <div class="chart-card wide">
        <div class="chart-header">
          <span class="chart-title">7日狀態趨勢 Status Trend</span>
        </div>
        <v-chart class="chart" :option="statusTrendOption" autoresize />
      </div>
    </div>

    <!-- Recent Orders Table -->
    <div class="pa-card recent-orders">
      <div class="chart-header">
        <span class="chart-title">最新訂單 Recent Orders</span>
        <el-button size="small" type="primary" plain @click="$router.push('/orders')">查看全部</el-button>
      </div>
      <el-table :data="recentOrders" size="small" class="orders-table" row-class-name="hover-row">
        <el-table-column prop="order_no" label="訂單號" width="140">
          <template #default="{ row }">
            <span class="order-no">{{ row.order_no }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="customer_name" label="客戶" width="100" />
        <el-table-column prop="route" label="路線" width="120" />
        <el-table-column prop="status" label="狀態" width="90">
          <template #default="{ row }">
            <span :class="'status-' + row.status_key">{{ row.status }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="weight" label="重量" width="80" />
        <el-table-column prop="created_at" label="日期" />
      </el-table>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { use } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { LineChart, BarChart, PieChart } from 'echarts/charts'
import { GridComponent, TooltipComponent, LegendComponent, TitleComponent } from 'echarts/components'
import VChart from 'vue-echarts'
import { Refresh, Box, Money, Warning, CircleCheck } from '@element-plus/icons-vue'
import dayjs from 'dayjs'

use([CanvasRenderer, LineChart, BarChart, PieChart, GridComponent, TooltipComponent, LegendComponent, TitleComponent])

const loading  = ref(false)
const trendDays = ref(30)
const today = dayjs().format('YYYY年MM月DD日')

// ── KPI cards ──
const kpis = ref([
  { label: '今日訂單 Orders', value: '142', trend: 8,  icon: 'Box',          iconBg: '#fff3e6', iconColor: '#e8851a' },
  { label: '本月收入 Revenue', value: 'HK$284,500', trend: 12, icon: 'Money', iconBg: '#e6f7ef', iconColor: '#2a9d5c' },
  { label: '待跟進 Pending',  value: '18',  trend: -3, icon: 'Warning',     iconBg: '#fdeee8', iconColor: '#d44e2a' },
  { label: '已完成 Completed',value: '1,284',trend: 5, icon: 'CircleCheck', iconBg: '#e6f7ef', iconColor: '#2a9d5c' },
])

// ── Recent orders (mock) ──
const recentOrders = ref([
  { order_no: 'PA-2024-0893', customer_name: '李偉強',     route: 'HK → SH', status: '運輸中', status_key: 'transit',   weight: '8.2 kg',  created_at: '今日 11:20' },
  { order_no: 'PA-2024-0892', customer_name: 'ABC Corp',   route: 'GZ → TW', status: '待取件', status_key: 'pending',   weight: '24.5 kg', created_at: '今日 10:45' },
  { order_no: 'PA-2024-0891', customer_name: 'Global Trade',route: 'GZ → HK', status: '已取件', status_key: 'active',    weight: '45 kg',   created_at: '今日 09:58' },
  { order_no: 'PA-2024-0890', customer_name: '張美玲',     route: 'HK → BJ', status: '異常',   status_key: 'exception', weight: '12.5 kg', created_at: '昨日 18:32' },
  { order_no: 'PA-2024-0889', customer_name: '陳志豪',     route: 'HK → TW', status: '已派送', status_key: 'active',    weight: '3.1 kg',  created_at: '昨日 14:10' },
])

// ── Chart: Trend Line ──
const trendOption = computed(() => {
  const days = Array.from({ length: trendDays.value }, (_, i) =>
    dayjs().subtract(trendDays.value - 1 - i, 'day').format('MM/DD')
  )
  const orders  = days.map(() => Math.floor(80 + Math.random() * 80))
  const completed = orders.map(v => Math.floor(v * 0.75))
  return {
    tooltip: { trigger: 'axis' },
    legend: { data: ['新增訂單', '已完成'], bottom: 4, textStyle: { fontSize: 11 }, itemGap: 20 },
    grid: { top: 10, right: 10, bottom: 54, left: 40 },
    xAxis: { type: 'category', data: days, axisLabel: { fontSize: 10 } },
    yAxis: { type: 'value', axisLabel: { fontSize: 10 } },
    series: [
      { name: '新增訂單', type: 'line', data: orders,    smooth: true, lineStyle: { color: '#e8851a', width: 2 }, itemStyle: { color: '#e8851a' }, areaStyle: { color: 'rgba(232,133,26,0.08)' } },
      { name: '已完成',  type: 'line', data: completed, smooth: true, lineStyle: { color: '#2a9d5c', width: 2 }, itemStyle: { color: '#2a9d5c' }, areaStyle: { color: 'rgba(42,157,92,0.06)' } },
    ],
  }
})

// ── Chart: Donut ──
const donutOption = computed(() => ({
  tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
  legend: { bottom: 4, textStyle: { fontSize: 10 }, itemGap: 12 },
  series: [{
    type: 'pie', radius: ['45%', '70%'], center: ['50%', '42%'],
    data: [
      { value: 642, name: '運輸中', itemStyle: { color: '#e8851a' } },
      { value: 284, name: '待取件', itemStyle: { color: '#c9a227' } },
      { value: 324, name: '已派送', itemStyle: { color: '#2a9d5c' } },
      { value: 34,  name: '異常',   itemStyle: { color: '#d44e2a' } },
    ],
    label: { fontSize: 10 },
  }],
}))

// ── Chart: Revenue Bar ──
const revenueOption = computed(() => {
  const months = ['1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月']
  return {
    tooltip: { trigger: 'axis' },
    legend: { data: ['收入', '支出'], bottom: 4, textStyle: { fontSize: 10 }, itemGap: 20 },
    grid: { top: 10, right: 10, bottom: 54, left: 50 },
    xAxis: { type: 'category', data: months, axisLabel: { fontSize: 9 } },
    yAxis: { type: 'value', axisLabel: { fontSize: 9, formatter: v => v >= 10000 ? (v/10000)+'萬' : v } },
    series: [
      { name: '收入', type: 'bar', data: [24,28,22,31,29,35,38,40,37,42,44,39].map(v=>v*1000), itemStyle: { color: '#e8851a', borderRadius: [3,3,0,0] }, barMaxWidth: 16 },
      { name: '支出', type: 'bar', data: [18,20,17,22,21,25,27,29,26,30,31,28].map(v=>v*1000), itemStyle: { color: '#f0c090', borderRadius: [3,3,0,0] }, barMaxWidth: 16 },
    ],
  }
})

// ── Chart: Status Trend Stacked Bar ──
const statusTrendOption = computed(() => {
  const days = Array.from({ length: 7 }, (_, i) =>
    dayjs().subtract(6 - i, 'day').format('MM/DD')
  )
  return {
    tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
    legend: { data: ['運輸中', '待取件', '已派送', '異常'], bottom: 4, textStyle: { fontSize: 10 }, itemGap: 16 },
    grid: { top: 10, right: 10, bottom: 54, left: 40 },
    xAxis: { type: 'category', data: days, axisLabel: { fontSize: 10 } },
    yAxis: { type: 'value', axisLabel: { fontSize: 10 } },
    series: [
      { name: '運輸中', type: 'bar', stack: 'total', data: [45,52,48,61,58,65,72], itemStyle: { color: '#e8851a' } },
      { name: '待取件', type: 'bar', stack: 'total', data: [20,18,22,25,24,22,28], itemStyle: { color: '#c9a227' } },
      { name: '已派送', type: 'bar', stack: 'total', data: [38,42,35,48,44,50,55], itemStyle: { color: '#2a9d5c' } },
      { name: '異常',   type: 'bar', stack: 'total', data: [3,2,5,4,3,2,4],        itemStyle: { color: '#d44e2a' } },
    ],
  }
})

function init() { /* In production would call dashboardApi */ }
onMounted(init)
</script>

<style scoped>
.dashboard {
  height: 100%;
  overflow-y: auto;
  padding: 24px;
  background: var(--pa-bg);
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.page-header { display: flex; justify-content: space-between; align-items: center; }
.page-title  { font-size: 20px; font-weight: 700; color: #2c2520; }
.page-sub    { font-size: 12px; color: #9e9890; margin-top: 2px; }

/* KPI Grid */
.kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
.kpi-card {
  background: #fff; border: 1px solid var(--pa-border); border-radius: 12px;
  padding: 18px 20px; display: flex; align-items: center; gap: 14px;
}
.kpi-icon {
  width: 44px; height: 44px; border-radius: 11px;
  display: flex; align-items: center; justify-content: center;
  font-size: 20px; flex-shrink: 0;
}
.kpi-value { font-size: 20px; font-weight: 800; color: #2c2520; line-height: 1.2; }
.kpi-label { font-size: 11px; color: #9e9890; margin-top: 2px; }
.kpi-change { margin-left: auto; font-size: 11px; font-weight: 700; }
.kpi-change.up   { color: #2a9d5c; }
.kpi-change.down { color: #d44e2a; }

/* Charts */
.charts-row { display: flex; gap: 14px; }
.chart-card {
  background: #fff; border: 1px solid var(--pa-border); border-radius: 12px;
  padding: 16px; flex: 1;
}
.chart-card.wide { flex: 2; }
.chart-header {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 12px;
}
.chart-title { font-size: 13px; font-weight: 700; color: #2c2520; }
.chart { height: 200px; }

/* Recent Orders */
.recent-orders { background: #fff; border: 1px solid var(--pa-border); border-radius: 12px; padding: 16px; }
.order-no { font-family: monospace; font-weight: 700; font-size: 12px; color: #e8851a; }
.orders-table { margin-top: 8px; }
</style>
