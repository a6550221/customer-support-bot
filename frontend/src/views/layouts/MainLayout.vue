<template>
  <el-container class="main-layout">
    <!-- Sidebar -->
    <el-aside width="220px" class="sidebar">
      <div class="logo">
        <el-icon size="22"><Headset /></el-icon>
        <span>HelpDesk</span>
      </div>

      <el-menu
        :default-active="$route.path"
        router
        background-color="#1e293b"
        text-color="#94a3b8"
        active-text-color="#ffffff"
        class="sidebar-menu"
      >
        <el-menu-item index="/tickets" class="menu-item-badged">
          <el-icon><Tickets /></el-icon>
          <span>{{ t('nav.inbox') }}</span>
          <span v-if="pendingCount > 0" class="menu-badge">{{ pendingCount }}</span>
        </el-menu-item>
        <el-menu-item index="/chat" class="menu-item-badged">
          <el-icon><ChatLineRound /></el-icon>
          <span>{{ t('nav.chat') }}</span>
          <span v-if="waitingChats > 0" class="menu-badge danger">{{ waitingChats }}</span>
        </el-menu-item>
        <el-menu-item index="/dashboard">
          <el-icon><TrendCharts /></el-icon>
          <span>{{ t('nav.dashboard') }}</span>
        </el-menu-item>
        <el-menu-item index="/knowledge">
          <el-icon><Reading /></el-icon>
          <span>{{ t('nav.knowledge') }}</span>
        </el-menu-item>
        <el-menu-item v-if="auth.isSupervisor" index="/settings">
          <el-icon><Setting /></el-icon>
          <span>{{ t('nav.settings') }}</span>
        </el-menu-item>
      </el-menu>
    </el-aside>

    <el-container class="content-area">
      <!-- Header -->
      <el-header class="top-header">
        <div class="header-right">
          <!-- Language switch -->
          <el-button-group class="lang-switch">
            <el-button size="small" :type="locale === 'zh' ? 'primary' : ''" @click="switchLang('zh')">中文</el-button>
            <el-button size="small" :type="locale === 'en' ? 'primary' : ''" @click="switchLang('en')">EN</el-button>
          </el-button-group>

          <!-- Notification bell with popover -->
          <el-popover placement="bottom-end" :width="320" trigger="click" popper-class="notif-popover">
            <template #reference>
              <el-badge :value="notifCount" :hidden="notifCount === 0" type="danger">
                <el-button :icon="Bell" circle />
              </el-badge>
            </template>
            <div class="notif-panel">
              <div class="notif-header">
                <span class="notif-title">通知</span>
                <el-button link size="small" @click="notifCount = 0">全部已讀</el-button>
              </div>
              <div v-if="waitingSessionsList.length === 0 && recentEvents.length === 0" class="notif-empty">
                <el-empty description="暫無通知" :image-size="50" />
              </div>
              <template v-for="s in waitingSessionsList" :key="'w'+s.id">
                <div class="notif-item" @click="goToChat(s)">
                  <div class="notif-dot waiting"></div>
                  <div class="notif-content">
                    <div class="notif-text">新對話請求：<strong>{{ s.visitor_name || '訪客' }}</strong></div>
                    <div class="notif-time">{{ dayjs(s.created_at).fromNow() }}</div>
                  </div>
                </div>
              </template>
              <template v-for="ev in recentEvents" :key="'e'+ev.id">
                <div class="notif-item">
                  <div class="notif-dot"></div>
                  <div class="notif-content">
                    <div class="notif-text">{{ ev.text }}</div>
                    <div class="notif-time">{{ dayjs(ev.time).fromNow() }}</div>
                  </div>
                </div>
              </template>
            </div>
          </el-popover>

          <!-- Agent status + user dropdown -->
          <el-dropdown @command="handleUserCmd">
            <div class="user-info">
              <el-avatar :size="32" :src="auth.user?.avatar">{{ auth.user?.name?.[0] }}</el-avatar>
              <span class="user-name">{{ auth.user?.name }}</span>
              <el-tag :type="statusType" size="small">{{ t('common.' + (auth.user?.status || 'offline')) }}</el-tag>
            </div>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item command="online">🟢 {{ t('common.online') }}</el-dropdown-item>
                <el-dropdown-item command="busy">🟡 {{ t('common.busy') }}</el-dropdown-item>
                <el-dropdown-item command="offline">⚫ {{ t('common.offline') }}</el-dropdown-item>
                <el-dropdown-item divided command="logout">{{ t('common.logout') }}</el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
      </el-header>

      <!-- Main content -->
      <el-main class="main-content">
        <router-view />
      </el-main>
    </el-container>
  </el-container>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useTicketStore } from '@/stores/tickets'
import { useChatStore } from '@/stores/chat'
import { Bell } from '@element-plus/icons-vue'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'
dayjs.extend(relativeTime)

const { t, locale } = useI18n()
const router    = useRouter()
const auth      = useAuthStore()
const ticketStore = useTicketStore()
const chatStore   = useChatStore()

const notifCount  = ref(0)
const recentEvents = ref([])

const pendingCount = computed(() => ticketStore.tickets.filter(t => t.status === 'open').length)
const waitingChats = computed(() => chatStore.sessions.filter(s => s.status === 'waiting').length)
// Sessions shown in bell notification panel
const waitingSessionsList = computed(() =>
  chatStore.sessions.filter(s => s.status === 'waiting').slice(0, 5)
)
const statusType = computed(() => ({
  online: 'success', busy: 'warning', offline: 'info',
}[auth.user?.status] || 'info'))

function switchLang(lang) {
  locale.value = lang
  localStorage.setItem('lang', lang)
}

function goToChat(session) {
  router.push('/chat')
}

async function handleUserCmd(cmd) {
  if (cmd === 'logout') {
    await auth.logout()
    router.push('/login')
  } else {
    await auth.setStatus(cmd)
  }
}

let echo = null

onMounted(() => {
  ticketStore.fetchTickets()
  chatStore.fetchSessions()

  if (import.meta.env.VITE_PUSHER_APP_KEY) {
    window.Pusher = Pusher
    echo = new Echo({
      broadcaster: 'pusher',
      key: import.meta.env.VITE_PUSHER_APP_KEY,
      cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'ap3',
      forceTLS: true,
    })

    echo.channel('tickets').listen('.ticket.updated', e => {
      ticketStore.updateTicketInList(e.ticket)
      notifCount.value++
      recentEvents.value.unshift({ id: Date.now(), text: `工單更新：${e.ticket.ticket_no}`, time: new Date() })
      if (recentEvents.value.length > 10) recentEvents.value.pop()
    })

    echo.channel('chat-sessions').listen('.session.updated', e => {
      chatStore.onSessionUpdated(e.session)
      if (e.session.status === 'waiting') {
        notifCount.value++
      }
    })
  }
})

onUnmounted(() => {
  echo?.disconnect()
})
</script>

<style scoped>
.main-layout { height: 100vh; overflow: hidden; }

.sidebar {
  background: #1e293b;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.logo {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 20px 20px;
  color: #fff;
  font-size: 18px;
  font-weight: 700;
  border-bottom: 1px solid #334155;
}

.sidebar-menu { border-right: none; flex: 1; }

/* Badge on right side of menu item, vertically centered */
.menu-item-badged { position: relative; }
.menu-badge {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: #94a3b8;
  color: #fff;
  border-radius: 10px;
  min-width: 18px;
  height: 18px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 600;
  padding: 0 5px;
  line-height: 1;
}
.menu-badge.danger { background: #f56c6c; }

.content-area { flex-direction: column; overflow: hidden; }

.top-header {
  background: #fff;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding: 0 24px;
  height: 60px;
}

.header-right { display: flex; align-items: center; gap: 16px; }
.user-info { display: flex; align-items: center; gap: 8px; cursor: pointer; }
.user-name { font-size: 14px; font-weight: 500; }

.main-content { padding: 0; overflow: auto; background: #F8FAFC; }

/* Notification popover styles (global via popper-class) */
</style>

<style>
.notif-popover { padding: 0 !important; }
.notif-panel { max-height: 400px; overflow-y: auto; }
.notif-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 12px 16px; border-bottom: 1px solid #f1f5f9;
  position: sticky; top: 0; background: #fff; z-index: 1;
}
.notif-title { font-weight: 600; font-size: 14px; color: #1e293b; }
.notif-empty { padding: 24px; }
.notif-item {
  display: flex; align-items: flex-start; gap: 10px;
  padding: 12px 16px; cursor: pointer; border-bottom: 1px solid #f8fafc;
  transition: background .15s;
}
.notif-item:hover { background: #f8fafc; }
.notif-dot {
  width: 8px; height: 8px; border-radius: 50%;
  background: #94a3b8; flex-shrink: 0; margin-top: 5px;
}
.notif-dot.waiting { background: #f97316; }
.notif-content { flex: 1; }
.notif-text { font-size: 13px; color: #374151; line-height: 1.4; }
.notif-time { font-size: 11px; color: #94a3b8; margin-top: 2px; }
</style>
