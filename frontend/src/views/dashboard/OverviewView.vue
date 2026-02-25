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

// Temporary manual project switch logic based on Mock (in reality we fetch array of projects)
const availableProjects = ref([
  { id: 1, name: 'SaaS Alpha' },
  // { id: 2, name: 'Blog Beta' }
])

onMounted(() => {
  // Simulating auto-selection of the first project
  if (availableProjects.value.length > 0) {
    dashboardStore.setActiveProject(availableProjects.value[0].id)
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
  <div class="min-h-screen bg-gray-50 flex flex-col font-sans">
    
    <!-- Navbar -->
    <nav class="bg-white border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <LineChart class="h-8 w-8 text-primary-600" />
              <span class="ml-2 text-xl font-bold text-gray-900 tracking-tight">SaaS SEO</span>
            </div>
          </div>
          <div class="flex items-center space-x-6">
            
            <div class="flex items-center rounded-md border border-gray-200 bg-gray-50 px-3 py-1">
              <span class="text-sm text-gray-500 mr-2">Projeto:</span>
              <select 
                class="bg-transparent text-sm font-medium focus:ring-0 focus:outline-none"
                v-model="dashboardStore.activeProjectId"
                @change="dashboardStore.fetchAllData(Number(($event.target as HTMLSelectElement).value))"
              >
                <option v-for="proj in availableProjects" :key="proj.id" :value="proj.id">
                  {{ proj.name }}
                </option>
              </select>
            </div>

            <div class="h-6 w-px bg-gray-300"></div>

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
    <main class="flex-1 max-w-7xl w-full mx-auto py-8 px-4 sm:px-6 lg:px-8">
      
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
      <div v-else-if="dashboardStore.overview" class="space-y-6">
        
        <!-- Headers -->
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Dashboard SEO</h1>
          <p class="text-sm text-gray-500 mt-1">Visão geral para {{ dashboardStore.overview.project.domain }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          
          <!-- SEO Score Card -->
          <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-gray-500 uppercase tracking-wide">SEO Score</span>
              <Activity class="h-5 w-5 text-gray-400" />
            </div>
            <div class="mt-4">
              <span class="text-5xl font-black" :class="seoScoreClass">
                {{ dashboardStore.overview.seo_score?.score || 0 }}<span class="text-2xl text-gray-400 font-medium">/100</span>
              </span>
            </div>
          </div>

          <!-- Total Clicks Card -->
          <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-gray-500 uppercase tracking-wide">Cliques Orgânicos</span>
              <BarChart3 class="h-5 w-5 text-gray-400" />
            </div>
            <div class="mt-4 flex items-end justify-between">
              <span class="text-3xl font-bold text-gray-900">{{ dashboardStore.performance?.totals?.clicks?.toLocaleString('pt-BR') || 0 }}</span>
              <span class="text-sm font-medium text-green-600 flex items-center bg-green-50 px-2 py-1 rounded-md">
                <TrendingUp class="h-3 w-3 mr-1"/> 12%
              </span>
            </div>
          </div>

          <!-- Valid URLs Card -->
           <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-gray-500 uppercase tracking-wide">URLs Válidas</span>
              <CheckCircle class="h-5 w-5 text-gray-400" />
            </div>
            <div class="mt-4">
              <span class="text-3xl font-bold text-gray-900">{{ dashboardStore.overview.url_stats?.valid || 0 }}</span>
              <span class="text-sm text-gray-500 ml-2">indexadas</span>
            </div>
          </div>

          <!-- CTR Card -->
           <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-gray-500 uppercase tracking-wide">CTR Médio</span>
              <Search class="h-5 w-5 text-gray-400" />
            </div>
            <div class="mt-4">
              <span class="text-3xl font-bold text-gray-900">{{ Number(dashboardStore.performance?.totals?.avg_ctr || 0).toFixed(2) }}%</span>
            </div>
          </div>

        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-8">
          
          <!-- Main Chart -->
          <div class="col-span-2 bg-white rounded-2xl p-6 shadow-sm border border-gray-100 min-h-[400px]">
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-lg font-bold text-gray-900">Tráfego vs Impressões</h3>
              <select class="text-sm bg-gray-50 border border-gray-200 rounded-md px-2 py-1 text-gray-600 focus:outline-none">
                <option>Últimos 30 Dias</option>
                <option>Últimos 7 Dias</option>
              </select>
            </div>
            
            <div class="w-full h-80">
              <PerformanceChart :chart-data="dashboardStore.performance?.chart_data || []" />
            </div>
          </div>

          <!-- Insights AI Feed -->
           <div class="col-span-1 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
            <div class="p-6 border-b border-gray-100 bg-gray-50/50">
              <h3 class="text-lg font-bold text-gray-900">Motor de Insights IA</h3>
              <p class="text-xs text-gray-500 mt-1">Anomalias e Oportunidades encontradas</p>
            </div>
            
            <div class="p-0 overflow-y-auto flex-1 max-h-[400px]">
              
              <div v-if="dashboardStore.insights.length === 0" class="p-8 text-center text-gray-500 text-sm">
                Nenhum insight novo no momento! Tudo dominado.
              </div>

              <div 
                v-for="insight in dashboardStore.insights" 
                :key="insight.id"
                class="p-5 border-b border-gray-100 hover:bg-gray-50 transition duration-150"
              >
                <div class="flex items-start">
                  <div class="flex-shrink-0 mt-0.5">
                    <TrendingDown v-if="insight.type === 'anomaly'" class="h-5 w-5 text-red-500" />
                    <Search v-else class="h-5 w-5 text-blue-500" />
                  </div>
                  <div class="ml-3">
                    <h4 class="text-sm font-semibold text-gray-900">
                      {{ insight.title }}
                      <span 
                        class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                        :class="insight.type === 'anomaly' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'"
                      >
                        {{ insight.type }}
                      </span>
                    </h4>
                    <p class="mt-1 text-sm text-gray-600 line-clamp-3">
                      {{ insight.description }}
                    </p>
                  </div>
                </div>
              </div>

            </div>
          </div>

        </div>
        
      </div>
    </main>
  </div>
</template>
