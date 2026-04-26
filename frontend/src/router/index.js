import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/auth/LoginView.vue'),
    meta: { requiresGuest: true },
  },
  {
    path: '/',
    component: () => import('@/views/layouts/MainLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '', redirect: '/tickets' },
      { path: 'tickets', name: 'Tickets', component: () => import('@/views/tickets/TicketListView.vue') },
      { path: 'tickets/:id', name: 'TicketDetail', component: () => import('@/views/tickets/TicketDetailView.vue') },
      { path: 'chat', name: 'Chat', component: () => import('@/views/chat/ChatView.vue') },
      { path: 'dashboard', name: 'Dashboard', component: () => import('@/views/dashboard/DashboardView.vue') },
      { path: 'knowledge', name: 'Knowledge', component: () => import('@/views/knowledge/KnowledgeView.vue') },
      { path: 'knowledge/:id', name: 'KnowledgeDetail', component: () => import('@/views/knowledge/KnowledgeDetailView.vue') },
      {
        path: 'settings',
        name: 'Settings',
        component: () => import('@/views/settings/SettingsView.vue'),
        meta: { roles: ['admin', 'supervisor'] },
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, _from, next) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth && !auth.token) {
    return next('/login')
  }
  if (to.meta.requiresGuest && auth.token) {
    return next('/')
  }
  if (to.meta.roles && !to.meta.roles.includes(auth.user?.role)) {
    return next('/tickets')
  }
  next()
})

export default router
