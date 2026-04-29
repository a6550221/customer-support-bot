<template>
  <div class="reports-view">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">報表中心 Reports</h1>
        <p class="page-sub">{{ currentYear }}年 · 數據統計與績效分析</p>
      </div>
      <div class="header-actions">
        <el-select v-model="selectedYear" size="small" style="width:100px" @change="init">
          <el-option label="2024年" :value="2024" />
          <el-option label="2023年" :value="2023" />
        </el-select>
        <el-button :icon="Download" type="primary" plain size="small">導出報表</el-button>
      </div>
    </div>

    <!-- Tabs -->
    <div class="report-tabs">
      <button v-for="t in tabList" :key="t.key" :class="['rtab', activeTab === t.key && 'active']" @click="activeTab = t.key">
        {{ t.label }}
      </button>
    </div>

    <!-- Tab: Annual Trend -->
    <div v-if="activeTab === 'trend'" class="tab-content">
      <div class="charts-row">
        <div class="chart-card wide">
          <div class="chart-header">
            <span class="chart-title">全年訂單趨勢 Annual Order Trend</span>
          </div>
          <v-chart class="chart-lg" :option="annualTrendOption" autoresize />
        </div>
        <div class="chart-card">
          <div class="chart-header">
            <span class="chart-title">訂單狀態分佈</span>
          </div>
          <v-chart class="chart-lg" :option="statusPieOption" autoresize />
        </div>
      </div>
      <div class="charts-row">
        <div class="chart-card">
          <div class="chart-header">
            <span class="chart-title">月均收入 Monthly Revenue</span>
          </div>
          <v-chart class="chart-md" :option="revenueOption" autoresize />
        </div>
        <div class="chart-card">
          <div class="chart-header">
            <span class="chart-title">路線分佈 Route Distribution</span>
          </div>
          <v-chart class="chart-md" :option="routeOption" autoresize />
        </div>
        <div class="chart-card">
          <div class="chart-header">
            <span class="chart-title">異常率趨勢 Exception Rate</span>
          </div>
          <v-chart class="chart-md" :option="exceptionOption" autoresize />
        </div>
      </div>
    </div>

    <!-- Tab: Agent Performance -->
    <div v-if="activeTab === 'agents'" class="tab-content">
      <div class="perf-header">
        <div class="perf-title">客服績效排名 Agent Performance</div>
        <div class="perf-period">{{ selectedYear }}年 · 全年統計</div>
      </div>
      <div class="agent-table-wrap">
        <table class="agent-table">
          <thead>
            <tr>
              <th>排名</th>
              <th>客服姓名</th>
              <th>處理訂單</th>
              <th>平均回應</th>
              <th>客戶滿意度</th>
              <th>解決率</th>
              <th>本月績效</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(a, idx) in agents" :key="a.name">
              <td><span :class="['rank-badge', idx === 0 ? 'gold' : idx === 1 ? 'silver' : idx === 2 ? 'bronze' : '']">{{ idx + 1 }}</span></td>
              <td class="agent-name">{{ a.name }}</td>
              <td class="num">{{ a.orders }}</td>
              <td class="num">{{ a.avgReply }}</td>
              <td>
                <div class="rating-bar">
                  <div class="rating-fill" :style="{ width: a.satisfaction + '%', background: a.satisfaction >= 90 ? '#2a9d5c' : '#e8851a' }" />
                </div>
                <span class="rating-num">{{ a.satisfaction }}%</span>
              </td>
              <td class="num">{{ a.resolveRate }}%</td>
              <td>
                <span :class="['perf-tag', a.perf]">{{ { excellent:'優秀', good:'良好', normal:'普通' }[a.perf] }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="charts-row" style="margin-top:16px">
        <div class="chart-card">
          <div class="chart-header"><span class="chart-title">各坐席訂單量對比</span></div>
          <v-chart class="chart-md" :option="agentOrderOption" autoresize />
        </div>
        <div class="chart-card wide">
          <div class="chart-header"><span class="chart-title">月度績效趨勢</span></div>
          <v-chart class="chart-md" :option="agentTrendOption" autoresize />
        </div>
      </div>
    </div>

    <!-- Tab: Auto Send Config -->
    <div v-if="activeTab === 'autosend'" class="tab-content">
      <div class="autosend-wrap">
        <div class="section-title">報表自動發送設定 Auto-Send Configuration</div>
        <div class="autosend-list">
          <div v-for="cfg in autoSendConfigs" :key="cfg.id" class="autosend-card">
            <div class="autosend-left">
              <div class="autosend-name">{{ cfg.name }}</div>
              <div class="autosend-desc">{{ cfg.desc }}</div>
              <div class="autosend-schedule">
                <span class="schedule-badge">{{ cfg.schedule }}</span>
                <span class="recipient-list">發送至: {{ cfg.recipients.join(', ') }}</span>
              </div>
            </div>
            <div class="autosend-right">
              <el-switch v-model="cfg.enabled" />
            </div>
          </div>
        </div>

        <div class="section-title" style="margin-top:28px">新增自動發送規則</div>
        <div class="add-autosend-form">
          <el-form :model="newConfig" label-width="80px" size="small">
            <el-form-item label="規則名稱"><el-input v-model="newConfig.name" /></el-form-item>
            <el-form-item label="報表類型">
              <el-select v-model="newConfig.type" style="width:100%">
                <el-option label="日報 — 每日訂單匯總" value="daily" />
                <el-option label="週報 — 本週績效分析" value="weekly" />
                <el-option label="月報 — 月度趨勢報表" value="monthly" />
                <el-option label="異常報告 — 即時通知" value="exception" />
              </el-select>
            </el-form-item>
            <el-form-item label="發送時間"><el-input v-model="newConfig.time" placeholder="如：每日 08:30" /></el-form-item>
            <el-form-item label="收件郵箱"><el-input v-model="newConfig.email" placeholder="多個郵箱用逗號分隔" /></el-form-item>
          </el-form>
          <div style="text-align:right;margin-top:8px">
            <el-button type="primary" size="small" @click="addConfig">建立規則</el-button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { use } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { LineChart, BarChart, PieChart, RadarChart } from 'echarts/charts'
import { GridComponent, TooltipComponent, LegendComponent } from 'echarts/components'
import VChart from 'vue-echarts'
import { Download } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import dayjs from 'dayjs'

use([CanvasRenderer, LineChart, BarChart, PieChart, RadarChart, GridComponent, TooltipComponent, LegendComponent])

const currentYear = dayjs().year()
const selectedYear = ref(2024)
const activeTab = ref('trend')
const tabList = [
  { key: 'trend',    label: '全年趨勢' },
  { key: 'agents',   label: '客服績效' },
  { key: 'autosend', label: '自動發送' },
]

const months = ['1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月']

const annualTrendOption = computed(() => ({
  tooltip: { trigger: 'axis' },
  legend: { data: ['新增訂單', '已完成', '異常'], bottom: 0, textStyle: { fontSize: 10 } },
  grid: { top: 10, right: 10, bottom: 30, left: 45 },
  xAxis: { type: 'category', data: months, axisLabel: { fontSize: 10 } },
  yAxis: { type: 'value', axisLabel: { fontSize: 10 } },
  series: [
    { name: '新增訂單', type: 'bar', data: [1820,2100,1950,2380,2280,2650,2890,3020,2780,3140,3340,2960], itemStyle: { color: '#e8851a', borderRadius: [3,3,0,0] }, barMaxWidth: 20 },
    { name: '已完成',   type: 'line', data: [1640,1890,1780,2180,2090,2440,2650,2780,2560,2890,3070,2720], smooth: true, lineStyle: { color: '#2a9d5c', width: 2 }, itemStyle: { color: '#2a9d5c' } },
    { name: '異常',     type: 'line', data: [48,52,44,61,55,72,68,75,62,80,84,71], smooth: true, lineStyle: { color: '#d44e2a', width: 2 }, itemStyle: { color: '#d44e2a' } },
  ],
}))

const statusPieOption = computed(() => ({
  tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
  legend: { bottom: 0, textStyle: { fontSize: 10 } },
  series: [{
    type: 'pie', radius: ['42%', '70%'], center: ['50%', '44%'],
    data: [
      { value: 12480, name: '已完成',  itemStyle: { color: '#2a9d5c' } },
      { value: 6420,  name: '運輸中',  itemStyle: { color: '#e8851a' } },
      { value: 2840,  name: '待取件',  itemStyle: { color: '#c9a227' } },
      { value: 652,   name: '異常',    itemStyle: { color: '#d44e2a' } },
    ],
    label: { fontSize: 10 },
  }],
}))

const revenueOption = computed(() => ({
  tooltip: { trigger: 'axis' },
  legend: { data: ['收入', '支出'], bottom: 0, textStyle: { fontSize: 10 } },
  grid: { top: 10, right: 10, bottom: 30, left: 50 },
  xAxis: { type: 'category', data: months, axisLabel: { fontSize: 9 } },
  yAxis: { type: 'value', axisLabel: { fontSize: 9, formatter: v => v >= 10000 ? (v/10000).toFixed(0)+'萬' : v } },
  series: [
    { name: '收入', type: 'bar', data: [24,28,22,31,29,35,38,40,37,42,44,39].map(v=>v*1000), itemStyle: { color: '#e8851a', borderRadius: [3,3,0,0] }, barMaxWidth: 14 },
    { name: '支出', type: 'bar', data: [18,20,17,22,21,25,27,29,26,30,31,28].map(v=>v*1000), itemStyle: { color: '#f0c090', borderRadius: [3,3,0,0] }, barMaxWidth: 14 },
  ],
}))

const routeOption = computed(() => ({
  tooltip: { trigger: 'item' },
  legend: { bottom: 0, textStyle: { fontSize: 10 } },
  series: [{
    type: 'pie', radius: '60%',
    data: [
      { value: 4820, name: 'HK → SH', itemStyle: { color: '#e8851a' } },
      { value: 3640, name: 'HK → BJ', itemStyle: { color: '#c9a227' } },
      { value: 2890, name: 'GZ → HK', itemStyle: { color: '#2a9d5c' } },
      { value: 2140, name: 'HK → TW', itemStyle: { color: '#3b82f6' } },
      { value: 1820, name: 'HK → SG', itemStyle: { color: '#8b5cf6' } },
    ],
    label: { fontSize: 10 },
  }],
}))

const exceptionOption = computed(() => ({
  tooltip: { trigger: 'axis' },
  grid: { top: 10, right: 10, bottom: 30, left: 40 },
  xAxis: { type: 'category', data: months, axisLabel: { fontSize: 9 } },
  yAxis: { type: 'value', axisLabel: { fontSize: 9, formatter: v => v + '%' }, max: 5 },
  series: [{
    type: 'line', smooth: true,
    data: [2.6, 2.5, 2.3, 2.6, 2.4, 2.7, 2.4, 2.5, 2.2, 2.5, 2.5, 2.4],
    lineStyle: { color: '#d44e2a', width: 2 },
    itemStyle: { color: '#d44e2a' },
    areaStyle: { color: 'rgba(212,78,42,0.08)' },
  }],
}))

const agents = ref([
  { name: '陳小明', orders: 1842, avgReply: '3.2分', satisfaction: 96, resolveRate: 94, perf: 'excellent' },
  { name: '王大華', orders: 1620, avgReply: '4.1分', satisfaction: 93, resolveRate: 91, perf: 'excellent' },
  { name: '林曉月', orders: 1438, avgReply: '3.8分', satisfaction: 91, resolveRate: 89, perf: 'good' },
  { name: '趙志遠', orders: 1240, avgReply: '5.2分', satisfaction: 87, resolveRate: 85, perf: 'good' },
  { name: '黃小芬', orders: 1080, avgReply: '6.4分', satisfaction: 82, resolveRate: 80, perf: 'normal' },
])

const agentOrderOption = computed(() => ({
  tooltip: { trigger: 'axis' },
  grid: { top: 10, right: 10, bottom: 30, left: 45 },
  xAxis: { type: 'category', data: agents.value.map(a => a.name), axisLabel: { fontSize: 10 } },
  yAxis: { type: 'value', axisLabel: { fontSize: 10 } },
  series: [{
    type: 'bar', data: agents.value.map(a => a.orders),
    itemStyle: { color: '#e8851a', borderRadius: [4,4,0,0] }, barMaxWidth: 32,
  }],
}))

const agentTrendOption = computed(() => ({
  tooltip: { trigger: 'axis' },
  legend: { data: agents.value.slice(0,3).map(a => a.name), bottom: 0, textStyle: { fontSize: 10 } },
  grid: { top: 10, right: 10, bottom: 30, left: 40 },
  xAxis: { type: 'category', data: months, axisLabel: { fontSize: 10 } },
  yAxis: { type: 'value', axisLabel: { fontSize: 10 } },
  series: [
    { name: '陳小明', type: 'line', smooth: true, data: [140,162,148,178,165,185,195,202,188,208,216,195], lineStyle: { color: '#e8851a' }, itemStyle: { color: '#e8851a' } },
    { name: '王大華', type: 'line', smooth: true, data: [120,138,128,152,142,162,170,178,165,182,190,172], lineStyle: { color: '#2a9d5c' }, itemStyle: { color: '#2a9d5c' } },
    { name: '林曉月', type: 'line', smooth: true, data: [105,118,110,132,125,140,148,156,144,160,166,150], lineStyle: { color: '#3b82f6' }, itemStyle: { color: '#3b82f6' } },
  ],
}))

const autoSendConfigs = ref([
  { id:1, name:'每日訂單日報', desc:'每日自動匯總訂單數量、狀態分佈，發送給管理層', schedule:'每日 08:00', recipients:['manager@primeaxis.com', 'admin@primeaxis.com'], enabled: true },
  { id:2, name:'每週績效週報', desc:'每週一發送上週客服績效統計，包含訂單量、滿意度', schedule:'每週一 09:00', recipients:['manager@primeaxis.com'], enabled: true },
  { id:3, name:'月度分析報告', desc:'每月初發送上月完整數據分析，含趨勢圖表', schedule:'每月1日 10:00', recipients:['ceo@primeaxis.com', 'manager@primeaxis.com'], enabled: false },
  { id:4, name:'異常訂單即時通知', desc:'有訂單進入異常狀態時立即通知相關客服及主管', schedule:'即時', recipients:['ops@primeaxis.com'], enabled: true },
])

const newConfig = ref({ name:'', type:'daily', time:'', email:'' })
function addConfig() {
  autoSendConfigs.value.push({
    id: Date.now(),
    name: newConfig.value.name || '新規則',
    desc: `自動${newConfig.value.type}報表`,
    schedule: newConfig.value.time || '待設置',
    recipients: newConfig.value.email.split(',').map(s => s.trim()).filter(Boolean),
    enabled: true,
  })
  newConfig.value = { name:'', type:'daily', time:'', email:'' }
  ElMessage.success('自動發送規則已建立')
}

function init() {}
</script>

<style scoped>
.reports-view { height: 100%; display: flex; flex-direction: column; padding: 24px; gap: 16px; background: var(--pa-bg); overflow: hidden; }
.page-header  { display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; }
.page-title   { font-size: 20px; font-weight: 700; color: #2c2520; }
.page-sub     { font-size: 12px; color: #9e9890; margin-top: 2px; }
.header-actions { display: flex; gap: 8px; align-items: center; }

/* Tabs */
.report-tabs { display: flex; gap: 4px; flex-shrink: 0; }
.rtab {
  padding: 8px 20px; border-radius: 8px; border: 1.5px solid var(--pa-border);
  background: #fff; font-size: 12px; font-weight: 600; color: #706560; cursor: pointer; transition: all 0.15s;
}
.rtab:hover { background: #fdf5ec; color: var(--pa-orange); }
.rtab.active { background: var(--pa-orange); color: #fff; border-color: var(--pa-orange); }

/* Content */
.tab-content { flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 16px; }
.charts-row  { display: flex; gap: 14px; }
.chart-card  { background: #fff; border: 1px solid var(--pa-border); border-radius: 12px; padding: 16px; flex: 1; }
.chart-card.wide { flex: 2; }
.chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.chart-title  { font-size: 13px; font-weight: 700; color: #2c2520; }
.chart-lg { height: 240px; }
.chart-md { height: 180px; }

/* Agent Performance */
.perf-header { display: flex; justify-content: space-between; align-items: center; padding: 4px 0; }
.perf-title  { font-size: 14px; font-weight: 700; color: #2c2520; }
.perf-period { font-size: 12px; color: #9e9890; }
.agent-table-wrap { background: #fff; border-radius: 12px; border: 1px solid var(--pa-border); overflow: hidden; }
.agent-table { width: 100%; border-collapse: collapse; }
.agent-table th { padding: 10px 14px; background: #faf8f5; font-size: 11px; color: #9e9890; font-weight: 700; text-align: left; border-bottom: 1px solid var(--pa-border); }
.agent-table td { padding: 12px 14px; font-size: 13px; color: #2c2520; border-bottom: 1px solid #faf8f5; }
.agent-table tr:last-child td { border-bottom: none; }
.agent-name { font-weight: 700; }
.num { font-weight: 600; font-family: monospace; }
.rank-badge { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 50%; background: #f0ece6; font-size: 11px; font-weight: 800; }
.rank-badge.gold   { background: #fde68a; color: #92400e; }
.rank-badge.silver { background: #e5e7eb; color: #374151; }
.rank-badge.bronze { background: #fed7aa; color: #92400e; }
.rating-bar { display: inline-block; width: 80px; height: 6px; background: #f0ece6; border-radius: 3px; vertical-align: middle; margin-right: 6px; overflow: hidden; }
.rating-fill { height: 100%; border-radius: 3px; }
.rating-num { font-size: 12px; font-weight: 700; }
.perf-tag { font-size: 10px; padding: 2px 8px; border-radius: 8px; font-weight: 700; }
.perf-tag.excellent { background: #e6f7ef; color: #2a9d5c; }
.perf-tag.good      { background: #fff3e6; color: #e8851a; }
.perf-tag.normal    { background: #f0ece6; color: #706560; }

/* Auto Send */
.autosend-wrap { background: #fff; border-radius: 12px; border: 1px solid var(--pa-border); padding: 20px; }
.section-title { font-size: 13px; font-weight: 700; color: #2c2520; margin-bottom: 14px; padding-bottom: 8px; border-bottom: 1px solid var(--pa-border); }
.autosend-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 10px; }
.autosend-card {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 16px; background: #faf8f5; border-radius: 10px; border: 1px solid var(--pa-border);
}
.autosend-name { font-size: 13px; font-weight: 700; color: #2c2520; margin-bottom: 4px; }
.autosend-desc { font-size: 11px; color: #9e9890; margin-bottom: 6px; }
.autosend-schedule { display: flex; align-items: center; gap: 8px; }
.schedule-badge { background: var(--pa-orange-light); color: var(--pa-orange); font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 6px; border: 1px solid var(--pa-orange-border); }
.recipient-list { font-size: 11px; color: #706560; }
.add-autosend-form { background: #faf8f5; border-radius: 10px; padding: 16px; }
</style>
