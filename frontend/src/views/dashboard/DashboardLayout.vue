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
  <div class="min-h-screen bg-[#050505] flex flex-col font-sans text-gray-300 antialiased selection:bg-indigo-500/30">
    
    <!-- Top Glowing Accent -->
    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 z-50"></div>

    <!-- Glowing Background Mesh -->
    <div class="pointer-events-none fixed inset-0 flex items-center justify-center overflow-hidden z-0">
      <div class="h-[40rem] w-[40rem] rounded-full bg-indigo-900/10 blur-3xl filter absolute -top-40 -left-20"></div>
      <div class="h-[30rem] w-[30rem] rounded-full bg-fuchsia-900/10 blur-3xl filter absolute top-40 -right-20"></div>
    </div>

    <!-- Navbar -->
    <nav class="bg-[#0A0A0A]/80 border-b border-white/5 backdrop-blur-xl sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20 items-center">
          <div class="flex items-center space-x-12">
            <!-- Brand -->
            <div class="flex-shrink-0 flex items-center cursor-pointer group" @click="router.push('/dashboard')">
              <div class="relative flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-br from-indigo-500/20 to-purple-500/20 border border-white/10 group-hover:border-indigo-500/50 transition duration-300">
                <LineChart class="h-5 w-5 text-indigo-400 group-hover:text-indigo-300 transition-colors" />
                <div class="absolute inset-0 rounded-xl bg-indigo-400/20 blur opacity-0 group-hover:opacity-100 transition duration-500"></div>
              </div>
              <span class="ml-3 text-2xl font-black tracking-tighter text-transparent bg-clip-text bg-gradient-to-br from-white to-gray-400">SEO Platform</span>
            </div>
            
            <!-- Links -->
            <div class="hidden md:flex space-x-1">
                <router-link to="/dashboard" 
                  class="relative px-4 py-2 font-medium text-sm rounded-lg transition-all duration-300"
                  :class="$route.path === '/dashboard' ? 'text-white bg-white/10 shadow-[inset_0_1px_1px_rgba(255,255,255,0.1)] ring-1 ring-white/5' : 'text-gray-400 hover:text-white hover:bg-white/5'">
                    Overview
                </router-link>
                <router-link to="/dashboard/projects" 
                  class="relative px-4 py-2 font-medium text-sm rounded-lg transition-all duration-300"
                  :class="$route.path.includes('/dashboard/projects') ? 'text-white bg-white/10 shadow-[inset_0_1px_1px_rgba(255,255,255,0.1)] ring-1 ring-white/5' : 'text-gray-400 hover:text-white hover:bg-white/5'">
                    Meus Projetos
                </router-link>
            </div>
          </div>

          <div class="flex items-center space-x-4">
            <div class="py-1.5 px-3 rounded-full bg-white/5 border border-white/5 flex items-center shadow-inner text-sm font-medium text-gray-200" v-if="authStore.user">
              <div class="w-2 h-2 rounded-full bg-emerald-500 mr-2 animate-pulse"></div>
              {{ authStore.user?.name }}
            </div>
            <button @click="handleLogout" class="px-3 py-1.5 text-sm text-red-400 hover:text-red-300 hover:bg-red-500/10 rounded-lg font-semibold transition-all">
              Desconectar
            </button>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1 w-full mx-auto py-10 z-10 relative">
      <RouterView />
    </main>

  </div>
</template>
