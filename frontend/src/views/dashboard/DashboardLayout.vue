<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import { useRouter, RouterView } from 'vue-router'
import { LineChart, FolderGit2 } from 'lucide-vue-next'

const authStore = useAuthStore()
const router = useRouter()

const handleLogout = async () => {
  await authStore.logout()
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex flex-col font-sans">
    
    <!-- Navbar -->
    <nav class="bg-white border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex space-x-8">
            <div class="flex-shrink-0 flex items-center cursor-pointer" @click="router.push('/dashboard')">
              <LineChart class="h-8 w-8 text-primary-600" />
              <span class="ml-2 text-xl font-bold text-gray-900 tracking-tight">SaaS SEO</span>
            </div>
            
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                <router-link to="/dashboard" class="text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 font-medium text-sm" :class="$route.path === '/dashboard' ? 'border-primary-500' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'">
                    Overview
                </router-link>
                <router-link to="/dashboard/projects" class="text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 font-medium text-sm" :class="$route.path.includes('/dashboard/projects') ? 'border-primary-500' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'">
                    Meus Projetos
                </router-link>
            </div>
          </div>

          <div class="flex items-center space-x-6">
            <div class="text-sm font-medium text-gray-700" v-if="authStore.user">
              {{ authStore.user?.name }}
            </div>
            <button @click="handleLogout" class="text-sm text-red-600 hover:text-red-800 font-semibold transition-colors">
              Sair
            </button>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1 w-full mx-auto py-8">
      <RouterView />
    </main>

  </div>
</template>
