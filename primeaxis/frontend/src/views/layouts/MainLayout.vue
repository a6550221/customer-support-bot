<template>
  <div class="app-shell" :class="{ topbar: layoutMode === 'topbar' }">

    <!-- ── TOPBAR layout ── -->
    <div v-if="layoutMode === 'topbar'" class="topbar-nav">
      <div class="pa-logo">
        <div class="logo-icon"><PALogoSvg /></div>
        <div class="logo-text"><span class="logo-name">PrimeAxis</span><span class="logo-sub">LOGISTICS CRM</span></div>
      </div>
      <div class="divider-v" />
      <nav class="topbar-links">
        <router-link v-for="item in navItems" :key="item.path" :to="item.path" class="tb-link" active-class="tb-link-active">
          {{ item.label }}
          <span v-if="item.badge" class="nav-badge-dot">{{ item.badge }}</span>
        </router-link>
      </nav>
      <div class="topbar-right">
        <NotifBell />
        <div class="user-chip" @click="showUserMenu = !showUserMenu">
          <div class="user-avatar">{{ (auth.user?.name || 'CS')[0] }}</div>
          <div>
            <div class="user-name">{{ auth.user?.name || '客服員' }}</div>
            <div class="user-status">● {{ statusLabel }}</div>
          </div>
          <el-icon><ArrowDown /></el-icon>
        </div>
      </div>
    </div>

    <!-- ── SIDEBAR layout ── -->
    <aside v-else class="sidebar" :class="{ collapsed }">
      <div class="sidebar-logo">
        <div class="pa-logo">
          <div class="logo-icon"><PALogoSvg /></div>
          <template v-if="!collapsed">
            <div class="logo-text"><span class="logo-name">PrimeAxis</span><span class="logo-sub">LOGISTICS CRM</span></div>
          </template>
        </div>
        <el-icon v-if="!collapsed" class="collapse-btn" @click="collapsed = true"><ArrowLeft /></el-icon>
      </div>
      <div v-if="collapsed" class="expand-btn" @click="collapsed = false"><el-icon><ArrowRight /></el-icon></div>

      <nav class="sidebar-nav">
        <router-link
          v-for="item in navItems"
          :key="item.path"
          :to="item.path"
          class="nav-item"
          active-class="nav-item-active"
        >
          <div class="nav-icon-wrap">
            <el-icon><component :is="item.icon" /></el-icon>
            <span v-if="item.badge" class="nav-badge">{{ item.badge }}</span>
          </div>
          <template v-if="!collapsed">
            <div class="nav-labels">
              <span class="nav-label">{{ item.label }}</span>
              <span class="nav-sub">{{ item.sub }}</span>
            </div>
          </template>
        </router-link>
      </nav>

      <div class="sidebar-bottom">
        <NotifBell v-if="!collapsed" class="sidebar-bell" />
        <div class="user-info" @click="showUserMenu = !showUserMenu">
          <div class="user-avatar">{{ (auth.user?.name || 'CS')[0] }}</div>
          <template v-if="!collapsed">
            <div>
              <div class="user-name">{{ auth.user?.name || '客服員' }}</div>
              <div class="user-status online">● {{ statusLabel }}</div>
            </div>
          </template>
        </div>
      </div>
    </aside>

    <!-- ── MAIN CONTENT ── -->
    <main class="main-area">
      <router-view v-slot="{ Component, route }">
        <transition name="screen" mode="out-in">
          <component :is="Component" :key="route.path" />
        </transition>
      </router-view>
    </main>

    <!-- User dropdown menu -->
    <div v-if="showUserMenu" class="user-menu" :class="layoutMode">
      <div class="user-menu-header">
        <div class="user-avatar lg">{{ (auth.user?.name || 'CS')[0] }}</div>
        <div>
          <div class="user-menu-name">{{ auth.user?.name }}</div>
          <div class="user-menu-email">{{ auth.user?.email }}</div>
        </div>
      </div>
      <div class="user-menu-divider" />
      <div class="user-menu-item" @click="setStatus('online')">🟢 上線 Online</div>
      <div class="user-menu-item" @click="setStatus('busy')">🟡 忙碌 Busy</div>
      <div class="user-menu-item" @click="setStatus('offline')">⚫ 離線 Offline</div>
      <div class="user-menu-divider" />
      <div class="user-menu-item layout-switch" @click="toggleLayout">
        <el-icon><Grid /></el-icon>
        切換佈局：{{ layoutMode === 'sidebar' ? '側欄' : '頂欄' }}
      </div>
      <div class="user-menu-item danger" @click="handleLogout">登出 Logout</div>
    </div>
    <div v-if="showUserMenu" class="menu-overlay" @click="showUserMenu = false" />

  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { ArrowLeft, ArrowRight, ArrowDown, Grid,
  DataAnalysis, Headset, List, Calendar, DataLine, Setting } from '@element-plus/icons-vue'
import NotifBell from '@/components/NotifBell.vue'
import PALogoSvg from '@/components/PALogoSvg.vue'

const router      = useRouter()
const auth        = useAuthStore()
const collapsed   = ref(false)
const showUserMenu = ref(false)
const layoutMode  = ref(localStorage.getItem('pa_layout') || 'sidebar')

const navItems = [
  { path: '/dashboard', label: '儀表板', sub: 'Dashboard', icon: 'DataAnalysis' },
  { path: '/console',   label: '客服控台', sub: 'Console',   icon: 'Headset', badge: 2 },
  { path: '/orders',    label: '訂單管理', sub: 'Orders',    icon: 'List' },
  { path: '/followup',  label: '每日跟進', sub: 'Follow-up', icon: 'Calendar', badge: 3 },
  { path: '/reports',   label: '報表中心', sub: 'Reports',   icon: 'DataLine' },
  { path: '/settings',  label: '系統設定', sub: 'Settings',  icon: 'Setting' },
]

const statusLabel = computed(() => ({ online: '上線', busy: '忙碌', offline: '離線' }[auth.user?.status] || '上線'))

function toggleLayout() {
  layoutMode.value = layoutMode.value === 'sidebar' ? 'topbar' : 'sidebar'
  localStorage.setItem('pa_layout', layoutMode.value)
  showUserMenu.value = false
}

async function setStatus(s) {
  await auth.setStatus(s)
  showUserMenu.value = false
}

async function handleLogout() {
  showUserMenu.value = false
  await auth.logout()
  router.push('/login')
}
</script>

<style scoped>
/* ── Shell ── */
.app-shell { display: flex; height: 100vh; overflow: hidden; }
.app-shell.topbar { flex-direction: column; }

/* ── Sidebar ── */
.sidebar {
  width: var(--pa-sidebar);
  background: #fff;
  border-right: 1px solid var(--pa-border);
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  transition: width 0.22s ease;
  box-shadow: 2px 0 12px rgba(0,0,0,0.04);
  z-index: 10;
}
.sidebar.collapsed { width: 64px; }

.sidebar-logo {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 16px 16px 16px;
  min-height: 64px;
  border-bottom: 1px solid var(--pa-border);
}

.pa-logo { display: flex; align-items: center; gap: 10px; }
.logo-icon {
  width: 36px; height: 36px; border-radius: 10px;
  background: #0d4a38;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.logo-text { display: flex; flex-direction: column; }
.logo-name { font-size: 14px; font-weight: 800; color: #2c2520; letter-spacing: -0.3px; line-height: 1.2; }
.logo-sub  { font-size: 8px; font-weight: 700; color: #0d4a38; letter-spacing: 1px; text-transform: uppercase; }

.collapse-btn { cursor: pointer; color: #9e9890; font-size: 16px; }
.expand-btn {
  display: flex; justify-content: center; padding: 8px;
  cursor: pointer; color: #9e9890; font-size: 16px;
}
.expand-btn:hover { color: var(--pa-orange); }

/* ── Nav ── */
.sidebar-nav {
  flex: 1;
  padding: 10px 8px;
  display: flex;
  flex-direction: column;
  gap: 2px;
  overflow-y: auto;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 10px;
  border-radius: 9px;
  cursor: pointer;
  text-decoration: none;
  color: #706560;
  font-weight: 400;
  transition: all 0.14s;
  position: relative;
}
.sidebar.collapsed .nav-item { justify-content: center; padding: 12px 0; }
.nav-item:hover { background: var(--pa-orange-hover); color: var(--pa-orange); }
.nav-item-active {
  background: var(--pa-orange-light) !important;
  color: var(--pa-orange) !important;
  font-weight: 600;
}
.nav-item-active::before {
  content: '';
  position: absolute;
  left: -8px;
  top: 50%;
  transform: translateY(-50%);
  width: 3px;
  height: 20px;
  background: var(--pa-orange);
  border-radius: 0 3px 3px 0;
}

.nav-icon-wrap { position: relative; flex-shrink: 0; font-size: 18px; width: 22px; text-align: center; }
.nav-badge {
  position: absolute;
  top: -5px; right: -6px;
  min-width: 14px; height: 14px;
  background: var(--pa-danger); color: #fff;
  border-radius: 10px; font-size: 8px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  padding: 0 3px; border: 1.5px solid #fff;
}

.nav-labels { display: flex; flex-direction: column; }
.nav-label  { font-size: 13px; line-height: 1.25; }
.nav-sub    { font-size: 9px; opacity: 0.55; font-weight: 400; }

/* ── Sidebar Bottom ── */
.sidebar-bottom {
  padding: 10px 8px 14px;
  border-top: 1px solid var(--pa-border);
}
.sidebar-bell { padding: 0 10px 10px; }
.user-info {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
  border-radius: 9px;
  cursor: pointer;
  transition: background 0.14s;
}
.user-info:hover { background: var(--pa-orange-hover); }
.sidebar.collapsed .user-info { justify-content: center; }

.user-avatar {
  width: 30px; height: 30px; border-radius: 50%;
  background: #0d4a38; color: #fff;
  font-size: 11px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.user-avatar.lg { width: 36px; height: 36px; font-size: 13px; }
.user-name   { font-size: 12px; font-weight: 600; color: #2c2520; }
.user-status { font-size: 10px; color: #2a9d5c; }

/* ── Topbar ── */
.topbar-nav {
  display: flex;
  align-items: center;
  height: 56px;
  padding: 0 24px;
  gap: 8px;
  background: #fff;
  border-bottom: 1px solid var(--pa-border);
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  flex-shrink: 0;
}
.divider-v { width: 1px; height: 20px; background: var(--pa-border); margin: 0 8px; }
.topbar-links { display: flex; gap: 2px; flex: 1; }
.tb-link {
  padding: 6px 12px; border-radius: 7px;
  font-size: 13px; font-weight: 400; color: #706560;
  text-decoration: none; cursor: pointer; transition: all 0.14s;
  display: flex; align-items: center; gap: 5px; position: relative;
}
.tb-link:hover { background: var(--pa-orange-hover); color: var(--pa-orange); }
.tb-link-active { background: var(--pa-orange-light); color: var(--pa-orange); font-weight: 700; }
.nav-badge-dot {
  min-width: 14px; height: 14px; padding: 0 3px;
  background: var(--pa-danger); color: #fff; border-radius: 10px;
  font-size: 9px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
}
.topbar-right {
  display: flex; align-items: center; gap: 10px;
}
.user-chip {
  display: flex; align-items: center; gap: 8px;
  padding: 6px 10px; border-radius: 9px; cursor: pointer;
  transition: background 0.14s;
}
.user-chip:hover { background: var(--pa-orange-hover); }

/* ── Main Area ── */
.main-area { flex: 1; overflow: hidden; display: flex; flex-direction: column; }

/* ── User Menu ── */
.user-menu {
  position: fixed; bottom: 80px; left: 8px;
  width: 220px;
  background: #fff; border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.14);
  border: 1px solid var(--pa-border);
  z-index: 1000;
  overflow: hidden;
  animation: fadeSlideIn 0.15s ease;
}
.user-menu.topbar { bottom: auto; top: 64px; left: auto; right: 24px; }
.user-menu-header { display: flex; align-items: center; gap: 10px; padding: 14px 16px; }
.user-menu-name  { font-size: 13px; font-weight: 700; color: #2c2520; }
.user-menu-email { font-size: 11px; color: #9e9890; }
.user-menu-divider { height: 1px; background: var(--pa-border); }
.user-menu-item {
  padding: 10px 16px; font-size: 13px; color: #2c2520;
  cursor: pointer; display: flex; align-items: center; gap: 8px;
  transition: background 0.12s;
}
.user-menu-item:hover { background: var(--pa-orange-hover); }
.user-menu-item.danger { color: var(--pa-danger); }
.user-menu-item.danger:hover { background: #fdeee8; }
.menu-overlay {
  position: fixed; inset: 0; z-index: 999;
}

/* ── Screen Transition ── */
.screen-enter-active { animation: fadeSlideIn 0.18s ease; }
@keyframes fadeSlideIn {
  from { opacity: 0; transform: translateY(5px); }
  to   { opacity: 1; transform: translateY(0); }
}
</style>
