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
        <el-menu-item index="/tickets">
          <el-icon><Tickets /></el-icon>
          <span>{{ t('nav.inbox') }}</span>
          <el-badge v-if="pendingCount > 0" :value="pendingCount" class="badge" />
        </el-menu-item>
        <el-menu-item index="/chat">
          <el-icon><ChatLineRound /></el-icon>
          <span>{{ t('nav.chat') }}</span>
          <el-badge v-if="waitingChats > 0" :value="waitingChats" type="danger" class="badge" />
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

          <!-- Notification bell -->
          <el-badge :value="notifCount" :hidden="notifCount === 0">
            <el-button :icon="Bell" circle @click="showNotifs = true" />
          </el-badge>

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

const { t, locale } = useI18n()
const router    = useRouter()
const auth      = useAuthStore()
const ticketStore = useTicketStore()
const chatStore   = useChatStore()

const showNotifs = ref(false)
const notifCount = ref(0)

const pendingCount = computed(() => ticketStore.tickets.filter(t => t.status === 'open').length)
const waitingChats = computed(() => chatStore.sessions.filter(s => s.status === 'waiting').length)
const statusType   = computed(() => ({
  online: 'success', busy: 'warning', offline: 'info',
}[auth.user?.status] || 'info'))

function switchLang(lang) {
  locale.value = lang
  localStorage.setItem('lang', lang)
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
    })

    echo.channel('chat-sessions').listen('.session.updated', e => {
      chatStore.onSessionUpdated(e.session)
      if (e.session.status === 'waiting') notifCount.value++
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
.sidebar-menu .el-menu-item { position: relative; }
.badge { position: absolute; right: 16px; top: 50%; transform: translateY(-50%); }

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
.lang-switch { }
.user-info { display: flex; align-items: center; gap: 8px; cursor: pointer; }
.user-name { font-size: 14px; font-weight: 500; }

.main-content { padding: 0; overflow: auto; background: #F8FAFC; }
</style>
