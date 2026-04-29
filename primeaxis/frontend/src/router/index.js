import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  { path: '/login', component: () => import('@/views/LoginView.vue') },
  {
    path: '/',
    component: () => import('@/views/layouts/MainLayout.vue'),
    redirect: '/dashboard',
    children: [
      { path: 'dashboard', component: () => import('@/views/dashboard/DashboardView.vue') },
      { path: 'console',   component: () => import('@/views/console/ConsoleView.vue') },
      { path: 'orders',    component: () => import('@/views/orders/OrdersView.vue') },
      { path: 'followup',  component: () => import('@/views/followup/FollowupView.vue') },
      { path: 'reports',   component: () => import('@/views/reports/ReportsView.vue') },
      { path: 'settings',  component: () => import('@/views/settings/SettingsView.vue') },
    ],
  },
  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  const token = localStorage.getItem('pa_token')
  if (!token && to.path !== '/login') return '/login'
})

export default router
