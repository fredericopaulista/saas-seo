<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import { 
    Activity, ArrowUpRight, TrendingUp, Search, Layers, 
    ChevronLeft, Settings, RefreshCcw, ExternalLink
} from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const project = ref<any>(null)
const seoScore = ref<any>(null)
const urlStats = ref<any>(null)
const performanceData = ref<any>(null)
const insights = ref<any[]>([])

const fetchProjectDetails = async () => {
    loading.value = true
    try {
        const pId = route.params.id
        
        // 1. Fetch Overview (Project Info, SEO Score, URL Stats)
        const overviewRes = await api.get(`/dashboard/projects/${pId}/overview`)
        project.value = overviewRes.data.project
        seoScore.value = overviewRes.data.seo_score
        urlStats.value = overviewRes.data.url_stats
        
        // 2. Fetch Performance
        const perfRes = await api.get(`/dashboard/projects/${pId}/performance`)
        performanceData.value = perfRes.data.totals
        
        // 3. Fetch Insights
        const insightsRes = await api.get(`/dashboard/projects/${pId}/insights`)
        insights.value = insightsRes.data.insights
        
    } catch (e) {
        console.error('Failed to load project details', e)
        alert('Não foi possível carregar os detalhes do projeto ou você não tem acesso a ele.')
        router.push('/dashboard/projects')
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    fetchProjectDetails()
})

const getScoreColor = (score: number) => {
    if (!score) return 'text-gray-400'
    if (score >= 90) return 'text-emerald-400'
    if (score >= 70) return 'text-amber-400'
    return 'text-red-400'
}

const forceSync = async () => {
    if (!project.value) return
    try {
        const res = await api.post(`/dashboard/projects/${project.value.id}/sync`)
        alert(res.data.message)
    } catch (e) {
        console.error(e)
        alert('Erro ao agendar sincronização.')
    }
}
</script>

<template>
  <div class="space-y-8 pb-10">
    
    <!-- Loading State -->
    <div v-if="loading" class="animate-pulse space-y-8">
        <div class="h-32 bg-white/5 border border-white/5 rounded-3xl"></div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div v-for="i in 4" :key="i" class="h-40 bg-white/5 border border-white/5 rounded-3xl"></div>
        </div>
        <div class="h-96 bg-white/5 border border-white/5 rounded-3xl"></div>
    </div>

    <!-- Ready State -->
    <template v-else-if="project">
        
        <!-- Header Panel -->
        <div class="bg-[#0A0A0A]/40 p-8 rounded-3xl shadow-2xl border border-white/5 backdrop-blur-md relative overflow-hidden flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
            <div class="absolute -right-20 -top-20 w-80 h-80 bg-indigo-500/10 rounded-full blur-3xl"></div>
            
            <div class="relative z-10 flex items-center gap-6">
                <button @click="router.push('/dashboard/projects')" class="p-3 bg-white/5 border border-white/10 hover:bg-white/10 rounded-2xl text-gray-400 hover:text-white transition-all shadow-lg group">
                    <ChevronLeft class="w-6 h-6 group-hover:-translate-x-1 transition-transform" />
                </button>
                <div>
                    <h1 class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-white to-gray-400 tracking-tight">{{ project.name }}</h1>
                    <div class="flex items-center gap-3 mt-2">
                        <a :href="project.domain" target="_blank" class="text-indigo-400 font-medium hover:text-indigo-300 transition-colors flex items-center gap-1.5 bg-indigo-500/10 px-3 py-1 rounded-lg border border-indigo-500/20 text-sm">
                            <ExternalLink class="w-3.5 h-3.5" />
                            {{ project.domain }}
                        </a>
                        <span class="text-xs text-emerald-400 border border-emerald-400/20 bg-emerald-400/10 px-2.5 py-1 rounded-lg font-bold uppercase tracking-wider">
                            Sync Ativo
                        </span>
                    </div>
                </div>
            </div>

            <div class="relative z-10 flex gap-3">
                <button @click="forceSync" class="bg-white/5 hover:bg-white/10 border border-white/10 text-white px-5 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 transition-colors shadow-lg">
                    <RefreshCcw class="w-4 h-4" />
                    Forçar Sincronização
                </button>
                <button class="bg-indigo-600 hover:bg-indigo-500 text-white px-5 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 transition-all shadow-[0_0_15px_rgba(79,70,229,0.3)]">
                    <Settings class="w-4 h-4" />
                    Ajustes
                </button>
            </div>
        </div>

        <!-- Metrics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            
            <!-- SEO Score -->
            <div class="relative overflow-hidden bg-gradient-to-b from-white/5 to-transparent p-6 rounded-3xl border border-white/5 flex flex-col justify-between group hover:border-white/10 transition-all duration-500 shadow-xl backdrop-blur-md">
                <div class="absolute -right-10 -top-10 w-32 h-32 bg-indigo-500/10 rounded-full blur-3xl group-hover:bg-indigo-500/20 transition-all duration-500"></div>
                <div class="flex items-center justify-between relative z-10">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">SEO Score Global</span>
                    <Activity class="h-5 w-5 text-gray-500 group-hover:text-indigo-400 transition-colors" />
                </div>
                <div class="mt-8 relative z-10">
                    <span class="text-5xl font-black drop-shadow-lg" :class="getScoreColor(seoScore?.score)">
                      {{ seoScore?.score || 0 }}<span class="text-2xl text-gray-600 font-bold mix-blend-overlay">/100</span>
                    </span>
                    <div class="mt-3 flex items-center text-xs text-gray-500 font-medium">
                        <TrendingUp class="w-3.5 h-3.5 mr-1" />
                        Averiguado nas últimas 24h
                    </div>
                </div>
            </div>

             <!-- Clicks (GSC) -->
            <div class="relative overflow-hidden bg-gradient-to-b from-white/5 to-transparent p-6 rounded-3xl border border-white/5 flex flex-col justify-between group hover:border-white/10 transition-all duration-500 shadow-xl backdrop-blur-md">
                <div class="absolute -right-10 -top-10 w-32 h-32 bg-emerald-500/10 rounded-full blur-3xl group-hover:bg-emerald-500/20 transition-all duration-500"></div>
                <div class="flex items-center justify-between relative z-10">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Cliques (30 Dias)</span>
                    <ArrowUpRight class="h-5 w-5 text-gray-500 group-hover:text-emerald-400 transition-colors" />
                </div>
                <div class="mt-8 relative z-10">
                    <span class="text-4xl font-black text-white drop-shadow-lg tabular-nums">
                      {{ performanceData?.clicks?.toLocaleString() || 0 }}
                    </span>
                    <div class="mt-3 flex items-center text-xs text-emerald-400 font-medium bg-emerald-400/10 w-fit px-2 py-1 rounded-md border border-emerald-400/20">
                        <TrendingUp class="w-3.5 h-3.5 mr-1" />
                        +14% vs. Mês Anterior
                    </div>
                </div>
            </div>

            <!-- Valid URLs -->
             <div class="relative overflow-hidden bg-gradient-to-b from-white/5 to-transparent p-6 rounded-3xl border border-white/5 flex flex-col justify-between group hover:border-white/10 transition-all duration-500 shadow-xl backdrop-blur-md">
                <div class="absolute -right-10 -top-10 w-32 h-32 bg-blue-500/10 rounded-full blur-3xl group-hover:bg-blue-500/20 transition-all duration-500"></div>
                <div class="flex items-center justify-between relative z-10">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Páginas Válidas</span>
                    <Layers class="h-5 w-5 text-gray-500 group-hover:text-blue-400 transition-colors" />
                </div>
                <div class="mt-8 relative z-10">
                    <span class="text-4xl font-black text-white drop-shadow-lg tabular-nums">
                      {{ urlStats?.valid?.toLocaleString() || 0 }}
                    </span>
                    <div class="mt-3 flex items-center text-xs text-gray-500 font-medium">
                        De um total de {{ urlStats?.total || 0 }} detectadas
                    </div>
                </div>
            </div>

            <!-- Avg CTR -->
            <div class="relative overflow-hidden bg-gradient-to-b from-white/5 to-transparent p-6 rounded-3xl border border-white/5 flex flex-col justify-between group hover:border-white/10 transition-all duration-500 shadow-xl backdrop-blur-md">
                <div class="absolute -right-10 -top-10 w-32 h-32 bg-purple-500/10 rounded-full blur-3xl group-hover:bg-purple-500/20 transition-all duration-500"></div>
                <div class="flex items-center justify-between relative z-10">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">CTR Médio</span>
                    <Search class="h-5 w-5 text-gray-500 group-hover:text-purple-400 transition-colors" />
                </div>
                <div class="mt-8 relative z-10">
                    <span class="text-4xl font-black text-white drop-shadow-lg tabular-nums">
                      {{ (performanceData?.avg_ctr ? performanceData.avg_ctr * 100 : 0).toFixed(2) }}%
                    </span>
                    <div class="mt-3 flex items-center text-xs text-gray-500 font-medium">
                        Posição Média: {{ performanceData?.avg_position?.toFixed(1) || '-' }}
                    </div>
                </div>
            </div>
            
        </div>

        <!-- Two Column Layout: Charts & Insights -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left Axis (Fake Chart Area) -->
            <div class="lg:col-span-2 bg-[#0A0A0A]/40 rounded-3xl border border-white/5 p-8 backdrop-blur-md shadow-2xl relative overflow-hidden flex flex-col min-h-[400px]">
                 <div class="absolute -left-20 -bottom-20 w-80 h-80 bg-indigo-500/5 rounded-full blur-3xl pointer-events-none"></div>
                 
                 <div class="flex justify-between items-center mb-6 relative z-10">
                    <h3 class="text-xl font-bold text-white tracking-tight">Evolução do Tráfego Orgânico</h3>
                    <select class="bg-white/5 border border-white/10 text-white rounded-xl px-4 py-2 text-sm font-medium focus:outline-none focus:border-indigo-500 appearance-none pr-8">
                        <option value="30">Últimos 30 dias</option>
                        <option value="90">Últimos 90 dias</option>
                    </select>
                </div>

                <div class="flex-1 flex items-center justify-center border border-white/5 rounded-2xl bg-white/5 relative z-10">
                    <!-- Note: In real app, integrate vue-chartjs here like in OverviewView -->
                    <p class="text-gray-500 font-medium flex flex-col items-center gap-2">
                        <Activity class="w-8 h-8 opacity-50" />
                        Nenhum gráfico processado ainda. O crôn ira popular.
                    </p>
                </div>
            </div>

            <!-- AI Insights Feed -->
            <div class="bg-[#0A0A0A]/40 rounded-3xl border border-white/5 p-8 backdrop-blur-md shadow-2xl relative overflow-hidden">
                <div class="flex justify-between items-center mb-6 relative z-10">
                    <h3 class="text-xl font-bold text-white tracking-tight">IA Insights</h3>
                    <div class="w-3 h-3 rounded-full bg-indigo-500 animate-pulse shadow-[0_0_10px_rgba(99,102,241,0.8)]"></div>
                </div>

                <div class="space-y-4 relative z-10">
                    <div v-if="insights.length === 0" class="text-center py-8">
                        <p class="text-sm text-gray-500">O robô não detectou anomalias no projeto {{ project.name }} hoje.</p>
                    </div>

                    <div v-for="insight in insights" :key="insight.id" class="p-4 rounded-2xl border border-white/5 bg-white/5 hover:bg-white/10 transition-colors group">
                        <div class="flex items-start justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-widest px-2 py-0.5 rounded border mb-2" 
                                :class="{
                                    'text-red-400 border-red-400/20 bg-red-400/10': insight.priority === 'High',
                                    'text-amber-400 border-amber-400/20 bg-amber-400/10': insight.priority === 'Medium',
                                    'text-emerald-400 border-emerald-400/20 bg-emerald-400/10': insight.priority === 'Low'
                                }">
                                {{ insight.priority }} Priority
                            </span>
                            <span class="text-xs text-gray-600 font-medium">{{ new Date(insight.created_at).toLocaleDateString() }}</span>
                        </div>
                        <h4 class="font-bold text-white text-sm mt-1 leading-snug">{{ insight.title }}</h4>
                        <p class="text-sm text-gray-400 mt-2 leading-relaxed line-clamp-2 group-hover:line-clamp-none transition-all">{{ insight.description }}</p>
                    </div>
                </div>
            </div>
            
        </div>

    </template>

  </div>
</template>
