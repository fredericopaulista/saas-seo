<script setup lang="ts">
import { onMounted, computed, ref } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useDashboardStore } from '../../stores/dashboard'
import { useRouter } from 'vue-router'
import { 
  BarChart3, 
  Activity, 
  Search, 
  AlertTriangle, 
  CheckCircle,
  TrendingDown,
  TrendingUp,
  LineChart
} from 'lucide-vue-next'
import PerformanceChart from '../../components/PerformanceChart.vue'

const authStore = useAuthStore()
const dashboardStore = useDashboardStore()
const router = useRouter()

const handleLogout = async () => {
  await authStore.logout()
}

// We fetch user's projects when the component mounts
onMounted(async () => {
  if (dashboardStore.projects.length === 0) {
    await dashboardStore.fetchProjects()
  }
})

const seoScoreClass = computed(() => {
  const s = dashboardStore.overview?.seo_score?.score || 0
  if (s >= 80) return 'text-green-600'
  if (s >= 50) return 'text-yellow-600'
  return 'text-red-600'
})

</script>

<template>
  <div class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8">
      
      <!-- Loading State -->
      <div v-if="dashboardStore.loading" class="flex flex-col items-center justify-center py-20">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
        <p class="mt-4 text-gray-500 text-sm font-medium">Buscando inteligência da API do Google...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="dashboardStore.error" class="bg-red-50 p-6 rounded-xl border border-red-100 flex items-start">
        <AlertTriangle class="h-6 w-6 text-red-500 mr-3 flex-shrink-0" />
        <div>
          <h3 class="text-red-800 font-semibold text-lg">Houve um Erro!</h3>
          <p class="text-red-700 mt-1">{{ dashboardStore.error }}</p>
        </div>
      </div>
      
      <!-- Loaded State -->
      <div v-else-if="dashboardStore.overview" class="space-y-8">
        
        <!-- Headers -->
        <div class="flex justify-between items-center bg-[#0A0A0A]/40 p-6 rounded-3xl shadow-2xl border border-white/5 backdrop-blur-md mb-8 relative overflow-hidden">
          <div class="absolute -right-20 -top-20 w-60 h-60 bg-indigo-500/10 rounded-full blur-3xl"></div>
          <div class="relative z-10">
            <h1 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-white to-gray-400 tracking-tight">Dashboard SEO</h1>
            <p class="text-sm text-gray-400 mt-2 font-medium">Análise em tempo real para <span class="text-indigo-400">{{ dashboardStore.overview.project.domain }}</span></p>
          </div>
          
           <div class="flex items-center rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 backdrop-blur-sm relative z-10">
              <span class="text-xs text-gray-500 mr-3 font-bold uppercase tracking-widest">Workspace</span>
              <select 
                class="bg-transparent text-sm font-semibold text-white focus:ring-0 focus:outline-none appearance-none [&>option]:bg-[#111]"
                v-model="dashboardStore.activeProjectId"
                @change="dashboardStore.setActiveProject(Number(($event.target as HTMLSelectElement).value))"
              >
                <option v-for="proj in dashboardStore.projects" :key="proj.id" :value="proj.id">
                  {{ proj.name }}
                </option>
              </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          
          <!-- SEO Score Card -->
          <div class="relative overflow-hidden bg-gradient-to-b from-white/5 to-transparent p-6 rounded-3xl border border-white/5 flex flex-col justify-between group hover:border-white/10 transition-all duration-500">
            <div class="absolute -right-10 -top-10 w-32 h-32 bg-indigo-500/10 rounded-full blur-3xl group-hover:bg-indigo-500/20 transition-all duration-500"></div>
            <div class="flex items-center justify-between relative z-10">
              <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">SEO Score</span>
              <Activity class="h-5 w-5 text-gray-500 group-hover:text-indigo-400 transition-colors" />
            </div>
            <div class="mt-6 relative z-10">
              <span class="text-5xl font-black drop-shadow-lg" :class="seoScoreClass">
                {{ dashboardStore.overview.seo_score?.score || 0 }}<span class="text-2xl text-gray-600 font-bold mix-blend-overlay">/100</span>
              </span>
            </div>
          </div>

          <!-- Total Clicks Card -->
          <div class="relative overflow-hidden bg-gradient-to-b from-white/5 to-transparent p-6 rounded-3xl border border-white/5 flex flex-col justify-between group hover:border-white/10 transition-all duration-500">
            <div class="absolute -left-10 -bottom-10 w-32 h-32 bg-emerald-500/10 rounded-full blur-3xl group-hover:bg-emerald-500/20 transition-all duration-500"></div>
            <div class="flex items-center justify-between relative z-10">
              <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Tráfego Orgânico</span>
              <BarChart3 class="h-5 w-5 text-gray-500 group-hover:text-emerald-400 transition-colors" />
            </div>
            <div class="mt-6 flex items-end justify-between relative z-10">
              <span class="text-4xl font-black text-white drop-shadow-md">{{ dashboardStore.performance?.totals?.clicks?.toLocaleString('pt-BR') || 0 }}</span>
              <span class="text-xs font-bold text-emerald-400 flex items-center bg-emerald-400/10 px-2.5 py-1 rounded-full border border-emerald-400/20">
                <TrendingUp class="h-3 w-3 mr-1"/> 12%
              </span>
            </div>
          </div>

          <!-- Valid URLs Card -->
           <div class="relative overflow-hidden bg-gradient-to-b from-white/5 to-transparent p-6 rounded-3xl border border-white/5 flex flex-col justify-between group hover:border-white/10 transition-all duration-500">
            <div class="flex items-center justify-between relative z-10">
              <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">URLs Indexadas</span>
              <CheckCircle class="h-5 w-5 text-gray-500 group-hover:text-blue-400 transition-colors" />
            </div>
            <div class="mt-6 relative z-10">
              <span class="text-4xl font-black text-white drop-shadow-md">{{ dashboardStore.overview.url_stats?.valid || 0 }}</span>
              <span class="text-xs font-semibold text-gray-500 ml-2 uppercase tracking-wide">Páginas</span>
            </div>
          </div>

          <!-- CTR Card -->
           <div class="relative overflow-hidden bg-gradient-to-b from-white/5 to-transparent p-6 rounded-3xl border border-white/5 flex flex-col justify-between group hover:border-white/10 transition-all duration-500">
            <div class="flex items-center justify-between relative z-10">
              <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">CTR Médio</span>
              <Search class="h-5 w-5 text-gray-500 group-hover:text-fuchsia-400 transition-colors" />
            </div>
            <div class="mt-6 relative z-10">
              <span class="text-4xl font-black text-white drop-shadow-md">{{ Number(dashboardStore.performance?.totals?.avg_ctr || 0).toFixed(2) }}%</span>
            </div>
          </div>

        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-8">
          
          <!-- Main Chart -->
          <div class="col-span-2 bg-[#0A0A0A]/60 rounded-3xl p-8 shadow-2xl border border-white/5 backdrop-blur-md min-h-[400px]">
            <div class="flex items-center justify-between mb-8">
              <h3 class="text-xl font-bold text-white tracking-tight">Evolução de Tráfego</h3>
              <select class="text-xs font-bold uppercase tracking-wider bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-gray-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 [&>option]:bg-[#111]">
                <option>Últimos 30 Dias</option>
                <option>Últimos 7 Dias</option>
              </select>
            </div>
            
            <div class="w-full h-80">
              <PerformanceChart :chart-data="dashboardStore.performance?.chart_data || []" />
            </div>
          </div>

          <!-- Insights AI Feed -->
           <div class="col-span-1 bg-[#0A0A0A]/60 rounded-3xl shadow-2xl border border-white/5 backdrop-blur-md overflow-hidden flex flex-col relative">
            <div class="absolute top-0 w-full h-1 bg-gradient-to-r from-transparent via-purple-500 to-transparent opacity-50"></div>
            
            <div class="p-6 border-b border-white/5 bg-white/[0.02]">
              <div class="flex items-center gap-3">
                <div class="w-2 h-2 rounded-full bg-purple-500 animate-ping"></div>
                <h3 class="text-lg font-bold text-white tracking-tight">Motor de I.A.</h3>
              </div>
              <p class="text-xs text-gray-500 mt-2 font-medium">Anomalias e Oportunidades detectadas</p>
            </div>
            
            <div class="p-0 overflow-y-auto flex-1 max-h-[400px] custom-scrollbar">
              
              <div v-if="dashboardStore.insights.length === 0" class="p-10 text-center text-gray-500 text-sm font-medium">
                <div class="w-12 h-12 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-4 border border-white/5">
                  <CheckCircle class="w-6 h-6 text-gray-600" />
                </div>
                Nenhum insight novo no momento.<br>A inteligência está monitorando.
              </div>

              <div 
                v-for="insight in dashboardStore.insights" 
                :key="insight.id"
                class="p-5 border-b border-white/5 hover:bg-white/5 transition duration-300 group cursor-default"
              >
                <div class="flex items-start">
                  <div class="flex-shrink-0 mt-0.5 p-2 rounded-xl" :class="insight.type === 'anomaly' ? 'bg-red-500/10' : 'bg-blue-500/10'">
                    <TrendingDown v-if="insight.type === 'anomaly'" class="h-4 w-4 text-red-500" />
                    <Search v-else class="h-4 w-4 text-blue-400" />
                  </div>
                  <div class="ml-4">
                    <h4 class="text-sm font-bold text-gray-200 group-hover:text-white transition-colors">
                      {{ insight.title }}
                      <span 
                        class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black tracking-wider uppercase"
                        :class="insight.type === 'anomaly' ? 'bg-red-500/20 text-red-400 border border-red-500/20' : 'bg-blue-500/20 text-blue-400 border border-blue-500/20'"
                      >
                        {{ insight.type }}
                      </span>
                    </h4>
                    <p class="mt-2 text-sm text-gray-500 line-clamp-3 leading-relaxed font-medium">
                      {{ insight.description }}
                    </p>
                  </div>
                </div>
              </div>

            </div>
          </div>

        </div>
        
      </div>
  </div>
</template>
