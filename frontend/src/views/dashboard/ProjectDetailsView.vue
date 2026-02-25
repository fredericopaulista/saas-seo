<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import { 
    Activity, ArrowUpRight, TrendingUp, Search, Layers, 
    ChevronLeft, Settings, RefreshCcw, ExternalLink, Lightbulb, BarChart3, AlertTriangle
} from 'lucide-vue-next'
import PerformanceChart from '@/components/PerformanceChart.vue'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const project = ref<any>(null)
const seoScore = ref<any>(null)
const urlStats = ref<any>(null)
const performanceData = ref<any>(null)
const chartData = ref<any[]>([])
const insights = ref<any[]>([])
const urls = ref<any[]>([])
const urlsPagination = ref<any>({ current_page: 1, last_page: 1 })

const activeTab = ref('traffic')

const syncError = ref<string | null>(null)
const showReconnect = ref(false)

const translateIndexStatus = (status: string) => {
    if (!status) return ''
    const map: Record<string, string> = {
        'Indexed': 'Indexada',
        'Discovered': 'Descoberta',
        'Error': 'Não Indexada',
        'Pending': 'Pendente'
    }
    return map[status] || status
}

const translateCoverage = (status: string) => {
    if (!status) return ''
    const map: Record<string, string> = {
        'Valid': 'Válida',
        'Error': 'Erro',
        'Pending': 'Pendente',
        'Discovered - currently not indexed': 'Descoberta - não indexada',
        'Crawled - currently not indexed': 'Rastreada - não indexada',
        'Page with redirect': 'Página com redirecionamento',
        'Excluded by ‘noindex’ tag': 'Excluída pela tag "noindex"',
        'Alternate page with proper canonical tag': 'Página com tag canônica',
        'Duplicate without user-selected canonical': 'Duplicada sem tag canônica',
        'Not found (404)': 'Não encontrada (404)',
        'Server error (5xx)': 'Erro no servidor (5xx)',
        'Soft 404': 'Soft 404',
        'Blocked by robots.txt': 'Bloqueada pelo robots.txt'
    }
    return map[status] || status
}

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
        chartData.value = perfRes.data.chart_data || []
        
        // 3. Fetch Insights and sort by Severity (High>Medium>Low)
        const insightsRes = await api.get(`/dashboard/projects/${pId}/insights`)
        const severityMap: Record<string, number> = { 'High': 3, 'Medium': 2, 'Low': 1 }
        insights.value = (insightsRes.data.insights || []).sort((a: any, b: any) => {
            return (severityMap[b.severity] || 0) - (severityMap[a.severity] || 0)
        })
        
        // 4. Fetch URLs
        await fetchUrls(1)
        
    } catch (e) {
        console.error('Failed to load project details', e)
        alert('Não foi possível carregar os detalhes do projeto ou você não tem acesso a ele.')
        router.push('/dashboard/projects')
    } finally {
        loading.value = false
    }
}

const fetchUrls = async (page: number = 1) => {
    try {
        const pId = route.params.id
        const res = await api.get(`/dashboard/projects/${pId}/urls?page=${page}`)
        urls.value = res.data.data
        urlsPagination.value = {
            current_page: res.data.current_page,
            last_page: res.data.last_page,
            total: res.data.total
        }
    } catch (e) {
        console.error('Failed to load urls', e)
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
    syncError.value = null
    showReconnect.value = false
    try {
        const res = await api.post(`/dashboard/projects/${project.value.id}/sync`)
        alert(res.data.message)
        // Refresh the page data automatically
        await fetchProjectDetails()
    } catch (e: any) {
        console.error(e)
        syncError.value = e.response?.data?.error || 'Erro ao agendar sincronização.'
        if (e.response?.status === 401) {
            showReconnect.value = true
        }
    }
}

const forceUrlInspection = async () => {
    if (!project.value) return
    try {
        const res = await api.post(`/dashboard/projects/${project.value.id}/inspect-urls`)
        alert(res.data.message)
    } catch (e: any) {
        console.error(e)
        alert(e.response?.data?.error || 'Erro ao agendar verificação de URLs.')
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

        <!-- Error Banner -->
        <div v-if="syncError" class="bg-red-500/10 border border-red-500/20 p-6 rounded-2xl flex flex-col md:flex-row justify-between items-start md:items-center gap-4 animate-in fade-in slide-in-from-top-4 duration-300">
            <div class="flex items-start gap-4">
                <div class="p-2 bg-red-500/20 rounded-lg text-red-400">
                    <Activity class="w-6 h-6" />
                </div>
                <div>
                    <h3 class="text-red-400 font-bold text-lg">Atenção Necessária</h3>
                    <p class="text-red-400/80 text-sm mt-1">{{ syncError }}</p>
                </div>
            </div>
            <a v-if="showReconnect" :href="`/api/auth/google?project_id=${project.id}`" class="bg-white text-gray-900 border border-gray-200 px-5 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 hover:bg-gray-50 transition-colors shadow-lg whitespace-nowrap">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22.56 12.25C22.56 11.47 22.49 10.74 22.38 10.04H12V14.22H17.92C17.67 15.58 16.88 16.73 15.72 17.51V20.21H19.29C21.37 18.29 22.56 15.54 22.56 12.25Z" fill="#4285F4"/>
                    <path d="M12 23C14.97 23 17.46 22.02 19.29 20.21L15.72 17.51C14.73 18.17 13.48 18.57 12 18.57C9.13 18.57 6.69 16.63 5.82 14.04H2.14V16.89C3.96 20.49 7.69 23 12 23Z" fill="#34A853"/>
                    <path d="M5.82 14.04C5.6 13.38 5.47 12.7 5.47 12C5.47 11.3 5.6 10.62 5.82 9.96V7.11H2.14C1.39 8.6 0.98 10.25 0.98 12C0.98 13.75 1.39 15.4 2.14 16.89L5.82 14.04Z" fill="#FBBC05"/>
                    <path d="M12 5.43C13.62 5.43 15.06 5.98 16.2 7.07L19.38 3.89C17.45 2.1 14.96 1 12 1C7.69 1 3.96 3.51 2.14 7.11L5.82 9.96C6.69 7.37 9.13 5.43 12 5.43Z" fill="#EA4335"/>
                </svg>
                Restabelecer Conexão
            </a>
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

        <!-- Tabs Navigation -->
        <div class="flex items-center gap-6 border-b border-gray-800 pb-px mb-6 mt-4">
            <button 
                @click="activeTab = 'traffic'" 
                :class="activeTab === 'traffic' ? 'text-white border-b-2 border-indigo-500 pb-4 -mb-px font-bold' : 'text-gray-500 border-b-2 border-transparent pb-4 -mb-px font-medium hover:text-gray-300'"
                class="flex items-center gap-2 transition-colors uppercase tracking-wider text-sm"
            >
                <BarChart3 class="w-4 h-4" /> Evolução de Tráfego
            </button>
            <button 
                @click="activeTab = 'insights'" 
                :class="activeTab === 'insights' ? 'text-white border-b-2 border-purple-500 pb-4 -mb-px font-bold' : 'text-gray-500 border-b-2 border-transparent pb-4 -mb-px font-medium hover:text-gray-300'"
                class="flex items-center gap-2 transition-colors uppercase tracking-wider text-sm"
            >
                <Lightbulb class="w-4 h-4" /> Tarefas IA Insights
                <span v-if="insights.length" class="ml-1 bg-purple-500/20 text-purple-400 py-0.5 px-2 rounded-full text-xs">{{ insights.length }}</span>
            </button>
            <button 
                @click="activeTab = 'urls'" 
                :class="activeTab === 'urls' ? 'text-white border-b-2 border-blue-500 pb-4 -mb-px font-bold' : 'text-gray-500 border-b-2 border-transparent pb-4 -mb-px font-medium hover:text-gray-300'"
                class="flex items-center gap-2 transition-colors uppercase tracking-wider text-sm"
            >
                <Layers class="w-4 h-4" /> Páginas do Site
                <span v-if="urlsPagination?.total" class="ml-1 bg-blue-500/20 text-blue-400 py-0.5 px-2 rounded-full text-xs">{{ urlsPagination.total }}</span>
            </button>
        </div>

        <!-- Content Area -->
        <div class="grid grid-cols-1 gap-8">
            
            <!-- Traffic Tab -->
            <div v-if="activeTab === 'traffic'" class="bg-[#0A0A0A]/40 rounded-3xl border border-white/5 p-8 backdrop-blur-md shadow-2xl relative overflow-hidden flex flex-col min-h-[400px]">
                 <div class="absolute -left-20 -bottom-20 w-80 h-80 bg-indigo-500/5 rounded-full blur-3xl pointer-events-none"></div>
                 
                 <div class="flex justify-between items-center mb-6 relative z-10">
                    <h3 class="text-xl font-bold text-white tracking-tight">Evolução do Tráfego Orgânico</h3>
                    <select class="bg-white/5 border border-white/10 text-white rounded-xl px-4 py-2 text-sm font-medium focus:outline-none focus:border-indigo-500 appearance-none pr-8">
                        <option value="30">Últimos 30 dias</option>
                    </select>
                </div>

                <div class="flex-1 w-full h-[400px] relative z-10 bg-white/5 rounded-2xl border border-white/5 p-4">
                    <PerformanceChart :chart-data="chartData" />
                </div>
            </div>

            <!-- AI Insights Tab -->
            <div v-if="activeTab === 'insights'" class="bg-[#0A0A0A]/40 rounded-3xl border border-white/5 p-8 backdrop-blur-md shadow-2xl relative overflow-hidden">
                <div class="flex justify-between items-center mb-6 relative z-10">
                    <h3 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                        <span>Recomendações Pendentes</span>
                    </h3>
                    <div class="w-3 h-3 rounded-full bg-purple-500 animate-pulse shadow-[0_0_10px_rgba(168,85,247,0.8)]"></div>
                </div>

                <div class="space-y-4 relative z-10">
                    <div v-if="insights.length === 0" class="text-center py-16 bg-white/5 rounded-2xl border border-white/5">
                        <Lightbulb class="w-12 h-12 text-gray-600 mx-auto mb-4 opacity-50" />
                        <h4 class="text-lg font-bold text-gray-300">Nenhuma tarefa pendente</h4>
                        <p class="text-sm text-gray-500 mt-2">O robô de inteligência artificial não detectou problemas ou oportunidades que exigem ação hoje.</p>
                    </div>

                    <div v-for="(insight, index) in insights" :key="insight.id" class="p-6 rounded-2xl border border-white/5 bg-white/5 hover:bg-white/10 transition-colors group flex gap-4 items-start">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm border shadow-lg"
                             :class="{
                                'text-red-400 border-red-500/30 bg-red-500/10': insight.severity === 'High',
                                'text-amber-400 border-amber-500/30 bg-amber-500/10': insight.severity === 'Medium',
                                'text-emerald-400 border-emerald-500/30 bg-emerald-500/10': insight.severity === 'Low'
                             }">
                            {{ index + 1 }}
                        </div>

                        <div class="flex-1">
                            <div class="flex items-start justify-between">
                                <span class="text-[10px] font-black uppercase tracking-widest px-2 py-0.5 rounded border mb-2 flex items-center gap-1 w-fit" 
                                    :class="{
                                        'text-red-400 border-red-400/20 bg-red-400/10': insight.severity === 'High',
                                        'text-amber-400 border-amber-400/20 bg-amber-400/10': insight.severity === 'Medium',
                                        'text-emerald-400 border-emerald-400/20 bg-emerald-400/10': insight.severity === 'Low'
                                    }">
                                    <AlertTriangle v-if="insight.severity === 'High'" class="w-3 h-3" />
                                    Prioridade {{ insight.severity === 'High' ? 'Alta' : (insight.severity === 'Medium' ? 'Média' : 'Baixa') }}
                                </span>
                                <span class="text-xs text-gray-500 font-medium whitespace-nowrap">{{ new Date(insight.created_at).toLocaleDateString() }}</span>
                            </div>
                            <h4 class="font-bold text-white text-lg mt-1 leading-snug">{{ insight.title }}</h4>
                            <p class="text-sm text-gray-400 mt-2 leading-relaxed whitespace-pre-line">{{ insight.description }}</p>

                            <!-- Example actionable button slot for future -->
                            <div class="mt-4 flex gap-2">
                                <button class="text-xs font-bold text-white bg-white/10 hover:bg-white/20 border border-white/10 px-3 py-1.5 rounded-lg transition-colors">
                                    Marcar como Resolvido
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- URLs Indexation Tab -->
            <div v-if="activeTab === 'urls'" class="bg-[#0A0A0A]/40 rounded-3xl border border-white/5 p-8 backdrop-blur-md shadow-2xl relative overflow-hidden">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 relative z-10 gap-4">
                    <h3 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                        <span>Páginas & Indexação no Google</span>
                    </h3>
                    
                    <button @click="forceUrlInspection" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2 transition-all shadow-[0_0_15px_rgba(37,99,235,0.3)]">
                        <Search class="w-4 h-4" />
                        Verificar Indexação Pendente
                    </button>
                </div>

                <div class="relative z-10 w-full overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-white/5 text-gray-500 text-xs uppercase tracking-widest bg-white/[0.02]">
                                <th class="p-4 font-black">URL</th>
                                <th class="p-4 font-black">Indexação</th>
                                <th class="p-4 font-black">Erro / Cobertura</th>
                                <th class="p-4 font-black w-40 text-right">Último Rastreio</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <tr v-for="url in urls" :key="url.id" class="border-b border-white/5 hover:bg-white/5 transition-colors">
                                <td class="p-4 text-gray-300 truncate max-w-sm" :title="url.url">{{ url.url }}</td>
                                <td class="p-4">
                                    <span v-if="url.index_status === 'Indexed'" class="inline-flex items-center bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-2 py-1 rounded text-xs font-bold uppercase tracking-wider">
                                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-2 animate-pulse"></div> Indexada
                                    </span>
                                    <span v-else-if="url.index_status === 'Discovered'" class="inline-flex items-center bg-amber-500/10 text-amber-400 border border-amber-500/20 px-2 py-1 rounded text-xs font-bold uppercase tracking-wider">
                                       Descoberta (Pend.)
                                    </span>
                                    <span v-else-if="url.index_status === 'Error'" class="inline-flex items-center bg-red-500/10 text-red-500 border border-red-500/20 px-2 py-1 rounded text-xs font-bold uppercase tracking-wider">
                                       Não Indexada
                                    </span>
                                    <span v-else class="text-gray-500 text-xs uppercase font-bold">{{ translateIndexStatus(url.index_status) }}</span>
                                </td>
                                <td class="p-4 text-gray-400 text-xs">{{ translateCoverage(url.coverage_status) }}</td>
                                <td class="p-4 text-right text-gray-500">{{ new Date(url.last_crawled).toLocaleDateString() }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-if="urls.length === 0" class="text-center py-10 text-gray-500">
                        Nenhuma URL detectada para este projeto ainda. O Sitemap pode estar processando.
                    </div>

                    <!-- Pagination -->
                    <div v-if="urlsPagination.last_page > 1" class="flex items-center justify-between mt-6 pt-4 border-t border-white/5">
                        <button 
                            @click="fetchUrls(urlsPagination.current_page - 1)" 
                            :disabled="urlsPagination.current_page === 1"
                            class="px-4 py-2 border border-white/10 rounded-xl bg-white/5 text-gray-400 text-sm font-bold hover:bg-white/10 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                            Anterior
                        </button>
                        <span class="text-sm font-medium text-gray-500">Apresentando página <span class="text-white">{{ urlsPagination.current_page }}</span> de <span class="text-white">{{ urlsPagination.last_page }}</span></span>
                        <button 
                            @click="fetchUrls(urlsPagination.current_page + 1)" 
                            :disabled="urlsPagination.current_page === urlsPagination.last_page"
                            class="px-4 py-2 border border-white/10 rounded-xl bg-white/5 text-gray-400 text-sm font-bold hover:bg-white/10 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                            Próxima
                        </button>
                    </div>
                </div>
            </div>
            
        </div>

    </template>

  </div>
</template>
