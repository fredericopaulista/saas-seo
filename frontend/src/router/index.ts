import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import AdminLayout from '../views/admin/AdminLayout.vue'
import DashboardLayout from '../views/dashboard/DashboardLayout.vue'
import AdminDashboardView from '../views/admin/AdminDashboardView.vue'
import AdminTenantsView from '../views/admin/AdminTenantsView.vue'
import AdminPlansView from '../views/admin/AdminPlansView.vue'
import AdminBillingView from '../views/admin/AdminBillingView.vue'
import AdminSystemView from '../views/admin/AdminSystemView.vue'
import AdminSettingsView from '../views/admin/AdminSettingsView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/dashboard'
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/auth/LoginView.vue'),
      meta: { guestOnly: true }
    },
    {
      path: '/dashboard',
      component: DashboardLayout,
      meta: { requiresAuth: true },
      children: [
        {
          path: '', // Defaults to overview
          name: 'dashboard',
          component: () => import('../views/dashboard/OverviewView.vue'),
        },
        {
          path: 'projects',
          name: 'projects',
          component: () => import('../views/dashboard/ProjectsView.vue'),
        }
      ]
    },
    {
      path: '/pricing',
      name: 'pricing',
      component: () => import('../views/billing/PricingView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/admin',
      component: AdminLayout,
      meta: { requiresAuth: true, requiresAdmin: true }, // Assuming admin routes require auth and admin role
      children: [
        {
          path: '', // Default child route for /admin
          name: 'admin-dashboard',
          component: AdminDashboardView,
        },
        {
          path: 'tenants', // /admin/tenants
          name: 'admin-tenants',
          component: AdminTenantsView,
        },
        {
          path: 'plans', // /admin/plans
          name: 'admin-plans',
          component: AdminPlansView,
        },
        {
          path: 'billing', // /admin/billing
          name: 'admin-billing',
          component: AdminBillingView,
        },
        {
          path: 'system', // /admin/system
          name: 'admin-system',
          component: AdminSystemView,
        },
        {
          path: 'settings', // /admin/settings
          name: 'admin-settings',
          component: AdminSettingsView,
        }
      ]
    }
  ],
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  // Always try to fetch user if we have a token but no user object
  if (authStore.token && !authStore.user) {
    await authStore.fetchUser()
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated()) {
    next('/login')
  } else if (to.meta.guestOnly && authStore.isAuthenticated()) {
    next('/dashboard')
  } else {
    next()
  }
})

export default router
