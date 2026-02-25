<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import api from '@/services/api'
import { Activity, Database, ServerCrash, Zap, RefreshCw } from 'lucide-vue-next'

const health = ref<any>(null)
const loading = ref(true)
let interval: any

onMounted(async () => {
    await fetchHealth()
    interval = setInterval(fetchHealth, 15000) // refresh every 15s
})

onUnmounted(() => {
    if (interval) clearInterval(interval)
})

const fetchHealth = async () => {
    try {
        const { data } = await api.get('/admin/system/health')
        health.value = data
    } catch (e) {
        console.error('Failed to load system health')
    } finally {
        loading.value = false
    }
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold tracking-tight text-white flex items-center gap-3">
          <Activity class="w-8 h-8 text-green-500" />
          Monitoramento Técnico
        </h1>
        <p class="text-gray-400 mt-2">Status da infraestrutura, Redis cache e workers do Laravel Horizon.</p>
      </div>
      <button @click="fetchHealth" class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2 transition-colors">
          <RefreshCw class="w-4 h-4" :class="{'animate-spin': loading}" />
          Refresh
      </button>
    </div>

    <!-- Skeleton Loader -->
    <div v-if="loading && !health" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 animate-pulse">
        <div v-for="i in 3" :key="i" class="h-48 bg-gray-900 rounded-xl border border-gray-800"></div>
    </div>

    <div v-else-if="health" class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- Redis Status -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 relative overflow-hidden">
            <div class="flex items-center space-x-4 mb-6">
              <div class="p-3 bg-red-500/10 text-red-500 rounded-lg">
                <Database class="w-6 h-6" />
              </div>
              <div>
                  <h3 class="text-white font-bold">Redis In-Memory</h3>
                  <div class="flex items-center gap-2 mt-1">
                      <span class="w-2 h-2 rounded-full" :class="health.redis.status === 'online' ? 'bg-green-500' : 'bg-red-500'"></span>
                      <span class="text-xs text-gray-400 uppercase tracking-widest">{{ health.redis.status }}</span>
                  </div>
              </div>
            </div>

            <div v-if="health.redis.status === 'online'" class="space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Memória Usada</span>
                    <span class="text-white font-medium">{{ health.redis.memory_used }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Clientes Conectados</span>
                    <span class="text-white font-medium">{{ health.redis.connected_clients }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Uptime (Dias)</span>
                    <span class="text-white font-medium">{{ health.redis.uptime_days }}</span>
                </div>
            </div>
            <div v-else class="text-sm text-red-400">
                O Redis não pôde ser contatado. O sistema de filas cairá em cascata.
            </div>
        </div>

        <!-- Queues Status -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 relative overflow-hidden">
            <div class="flex items-center space-x-4 mb-6">
              <div class="p-3 bg-indigo-500/10 text-indigo-500 rounded-lg">
                <ServerCrash class="w-6 h-6" />
              </div>
              <div>
                  <h3 class="text-white font-bold">Processamento (Queues)</h3>
                  <div class="flex items-center gap-2 mt-1">
                      <span class="text-xs text-gray-400 uppercase tracking-widest">Active Jobs</span>
                  </div>
              </div>
            </div>

            <div class="space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Default Pending</span>
                    <span class="text-white font-bold">{{ health.queues.default_pending }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">SEO Jobs Pending</span>
                    <span class="text-white font-bold flex gap-2 items-center">
                        <span v-if="health.queues.seo_pending > 1000" class="w-2 h-2 rounded-full bg-yellow-500 animate-pulse"></span>
                        {{ health.queues.seo_pending }}
                    </span>
                </div>
                <div class="flex justify-between text-sm mt-4 pt-4 border-t border-gray-800">
                    <span class="text-red-400">Failed Jobs</span>
                    <span class="text-red-400 font-bold">{{ health.queues.failed_jobs }}</span>
                </div>
            </div>
        </div>


        <!-- GSC Quota -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 relative overflow-hidden">
            <div class="flex items-center space-x-4 mb-6">
              <div class="p-3 bg-green-500/10 text-green-500 rounded-lg">
                <Zap class="w-6 h-6" />
              </div>
              <div>
                  <h3 class="text-white font-bold">Google API Quota</h3>
                  <div class="flex items-center gap-2 mt-1">
                      <span class="w-2 h-2 rounded-full" :class="health.gsc_quota.status === 'healthy' ? 'bg-green-500' : 'bg-yellow-500'"></span>
                      <span class="text-xs text-gray-400 uppercase tracking-widest">{{ health.gsc_quota.status }}</span>
                  </div>
              </div>
            </div>

            <div class="space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Projetos Ativos</span>
                    <span class="text-white font-bold">{{ health.gsc_quota.total_projects }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Daily Calls (Estimadas)</span>
                    <span class="text-white font-bold">~{{ health.gsc_quota.estimated_daily_calls }}</span>
                </div>
                <div class="mt-4 text-xs text-gray-500 pt-4 border-t border-gray-800 leading-relaxed">
                    O limite oficial da Search Console API é de ~100M queries por dia. Monitore apenas se a taxa de refresh subir astronomicamente.
                </div>
            </div>
        </div>

    </div>

  </div>
</template>
