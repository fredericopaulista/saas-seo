import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import AdminLayout from '../views/admin/AdminLayout.vue'
import AdminDashboardView from '../views/admin/AdminDashboardView.vue'
import AdminTenantsView from '../views/admin/AdminTenantsView.vue'

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
      name: 'dashboard',
      component: () => import('../views/dashboard/OverviewView.vue'),
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
