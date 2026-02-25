<script setup lang="ts">
import { RouterView, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { LayoutDashboard, Users, CreditCard, Settings, LogOut, Activity } from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()

const logout = async () => {
    // Basic logout handling for admin context
    // You could also specifically hit an admin logout endpoint
    await authStore.logout()
    router.push('/login')
}
</script>

<template>
  <div class="min-h-screen bg-gray-900 text-white flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-gray-950 border-r border-gray-800 flex flex-col">
      <div class="p-6 border-b border-gray-800">
        <h1 class="text-xl font-bold bg-gradient-to-r from-red-500 to-purple-500 bg-clip-text text-transparent">
          SaaS SUPER ADMIN
        </h1>
      </div>

      <nav class="flex-1 p-4 space-y-2">
        <router-link to="/admin" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 transition-colors" active-class="bg-gray-800 text-white border-l-4 border-red-500">
          <LayoutDashboard class="w-5 h-5" />
          <span>Dashboard Global</span>
        </router-link>

        <router-link to="/admin/tenants" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 transition-colors" active-class="bg-gray-800 text-white border-l-4 border-red-500">
          <Users class="w-5 h-5" />
          <span>Gestão de Tenants</span>
        </router-link>

        <router-link to="/admin/plans" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 transition-colors" active-class="bg-gray-800 text-white border-l-4 border-red-500">
          <CreditCard class="w-5 h-5 opacity-70" />
          <span>Planos Ativos</span>
        </router-link>

        <router-link to="/admin/billing" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 transition-colors" active-class="bg-gray-800 text-white border-l-4 border-red-500">
          <CreditCard class="w-5 h-5 text-indigo-400" />
          <span>Faturas Gateway</span>
        </router-link>

        <router-link to="/admin/system" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 transition-colors" active-class="bg-gray-800 text-white border-l-4 border-red-500">
        <router-link to="/admin/system" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 transition-colors" active-class="bg-gray-800 text-white border-l-4 border-red-500">
          <Activity class="w-5 h-5" />
          <span>Monitoramento Técnico</span>
        </router-link>

        <router-link to="/admin/settings" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 transition-colors" active-class="bg-gray-800 text-white border-l-4 border-red-500">
          <Settings class="w-5 h-5" />
          <span>Configurações Globais</span>
        </router-link>
      </nav>

      <div class="p-4 border-t border-gray-800">
        <button @click="logout" class="flex w-full items-center space-x-3 px-4 py-3 rounded-lg text-red-400 hover:bg-red-500/10 transition-colors">
          <LogOut class="w-5 h-5" />
          <span>Sair do Root</span>
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-x-hidden flex flex-col">
      <!-- Header -->
      <header class="bg-gray-950/50 backdrop-blur-md border-b border-gray-800 h-16 flex items-center px-8 justify-between sticky top-0 z-10">
        <h2 class="text-lg font-medium text-gray-300">Central de Comando</h2>
        <div class="flex items-center space-x-4">
          <div class="bg-red-500/20 text-red-400 px-3 py-1 rounded-full text-xs font-medium border border-red-500/50">
            God Mode Ativado
          </div>
          <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-purple-600 to-red-600 border-2 border-gray-800 flex items-center justify-center font-bold text-sm text-white">
            Root
          </div>
        </div>
      </header>

      <!-- Content Window -->
      <div class="p-8 flex-1 overflow-y-auto">
        <RouterView />
      </div>
    </main>
  </div>
</template>
