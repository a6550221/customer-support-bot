<template>
  <div class="notif-wrap" ref="wrapRef">
    <div class="bell-btn" :class="{ active: open }" @click="open = !open">
      <el-icon><Bell /></el-icon>
      <span class="bell-dot" />
    </div>

    <div v-if="open" class="notif-panel">
      <div class="notif-head">
        <span>通知 Notifications</span>
        <span class="mark-read" @click="unread = 0">全部已讀</span>
      </div>
      <div v-for="n in notifs" :key="n.id" class="notif-item">
        <span class="notif-dot" :style="{ background: n.color }" />
        <div>
          <div class="notif-text">{{ n.text }}</div>
          <div class="notif-time">{{ n.time }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Bell } from '@element-plus/icons-vue'

const open   = ref(false)
const unread = ref(4)
const wrapRef = ref()

const notifs = [
  { id: 1, text: '訂單 PA-2024-0890 異常需跟進', time: '2分鐘前', color: '#d44e2a' },
  { id: 2, text: '新來電：張美玲 +852 9123 4567', time: '5分鐘前', color: '#e8851a' },
  { id: 3, text: '週報已自動發送至3位收件人',      time: '今日 08:00', color: '#2a9d5c' },
  { id: 4, text: '訂單 PA-2024-0891 已取件',     time: '今日 09:58', color: '#2a9d5c' },
]

function onClickOutside(e) {
  if (wrapRef.value && !wrapRef.value.contains(e.target)) open.value = false
}
onMounted(() => document.addEventListener('click', onClickOutside))
onUnmounted(() => document.removeEventListener('click', onClickOutside))
</script>

<style scoped>
.notif-wrap { position: relative; }
.bell-btn {
  width: 34px; height: 34px; border-radius: 9px;
  background: #f7f5f0; display: flex; align-items: center; justify-content: center;
  cursor: pointer; position: relative; font-size: 16px;
  transition: background 0.14s;
}
.bell-btn:hover, .bell-btn.active { background: #fff3e6; color: #e8851a; }
.bell-dot {
  position: absolute; top: 7px; right: 7px;
  width: 7px; height: 7px; border-radius: 50%;
  background: #d44e2a; border: 1.5px solid #fff;
}

.notif-panel {
  position: absolute; right: 0; top: 40px;
  width: 280px; background: #fff; border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.12);
  border: 1px solid #f0ede8; z-index: 300;
  overflow: hidden;
  animation: fadeSlideIn 0.15s ease;
}
.notif-head {
  display: flex; justify-content: space-between; align-items: center;
  padding: 12px 14px; border-bottom: 1px solid #f7f5f0;
  font-size: 13px; font-weight: 700; color: #2c2520;
}
.mark-read { font-size: 10px; color: #e8851a; cursor: pointer; font-weight: 600; }
.notif-item {
  display: flex; gap: 8px; align-items: flex-start;
  padding: 10px 14px; border-bottom: 1px solid #faf8f5;
  cursor: pointer; transition: background 0.12s;
}
.notif-item:hover { background: #fdf5ec; }
.notif-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; margin-top: 4px; }
.notif-text { font-size: 12px; color: #2c2520; line-height: 1.4; }
.notif-time { font-size: 10px; color: #9e9890; margin-top: 2px; }

@keyframes fadeSlideIn {
  from { opacity: 0; transform: translateY(4px); }
  to   { opacity: 1; transform: translateY(0); }
}
</style>
