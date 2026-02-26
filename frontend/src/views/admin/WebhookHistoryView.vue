<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import api from '@/services/api'
import { 
    Activity, 
    RefreshCcw, 
    Search, 
    ChevronRight, 
    ChevronLeft, 
    History, 
    AlertCircle, 
    CheckCircle2, 
    Clock, 
    MinusCircle,
    Eye,
    Trash2
} from 'lucide-vue-next'
import { format } from 'date-fns'
import { ptBR } from 'date-fns/locale'

const events = ref<any[]>([])
const loading = ref(false)
const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0
})

const filters = ref({
    status: '',
    event_type: ''
})

const selectedEvent = ref<any>(null)
const showDetailModal = ref(false)

onMounted(() => {
    fetchEvents()
})

const fetchEvents = async (page = 1) => {
    loading.value = true
    try {
        const params: any = { page }
        if (filters.value.status) params.status = filters.value.status
        if (filters.value.event_type) params.event_type = filters.value.event_type

        const { data } = await api.get('/admin/webhooks', { params })
        events.value = data.data
        pagination.value = {
            current_page: data.current_page,
            last_page: data.last_page,
            total: data.total
        }
    } catch (e) {
        console.error('Error fetching webhooks', e)
    } finally {
        loading.value = false
    }
}

const viewDetail = (event: any) => {
    selectedEvent.value = event
    showDetailModal.value = true
}

const deleteEvent = async (id: number) => {
    if (!confirm('Tem certeza que deseja remover este registro de log?')) return
    try {
        await api.delete(`/admin/webhooks/${id}`)
        fetchEvents(pagination.value.current_page)
    } catch (e) {
        alert('Falha ao remover log.')
    }
}

const formatDate = (date: string) => {
    if (!date) return '-'
    return format(new Date(date), 'dd/MM/yyyy HH:mm:ss', { locale: ptBR })
}

const getStatusBadgeClass = (status: string) => {
    switch (status) {
        case 'processed': return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
        case 'failed': return 'bg-red-500/10 text-red-400 border-red-500/20'
        case 'ignored': return 'bg-gray-500/10 text-gray-400 border-gray-500/20'
        default: return 'bg-blue-500/10 text-blue-400 border-blue-500/20'
    }
}

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'processed': return CheckCircle2
        case 'failed': return AlertCircle
        case 'ignored': return MinusCircle
        default: return Clock
    }
}

const eventTypes = [
    'PAYMENT_RECEIVED',
    'PAYMENT_CONFIRMED',
    'PAYMENT_OVERDUE',
    'PAYMENT_DELETED',
    'PAYMENT_REFUNDED',
    'PAYMENT_REFUND_DENIED',
    'PAYMENT_CHARGEBACK_REQUESTED',
    'SUBSCRIPTION_DELETED'
]

</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex justify-between items-center border-b border-gray-800 pb-5">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-3">
                    <History class="w-6 h-6 text-gray-400" />
                    Histórico de Webhooks
                </h1>
                <p class="text-sm text-gray-400 mt-1">Auditoria em tempo real de todas as notificações enviadas pelo Asaas.</p>
            </div>
            <button @click="fetchEvents(1)" :disabled="loading" class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2 transition-colors">
                <RefreshCcw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
                Atualizar
            </button>
        </div>

        <!-- Filters -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Evento</label>
                <select v-model="filters.event_type" @change="fetchEvents(1)" class="w-full bg-gray-950 border border-gray-800 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500">
                    <option value="">Todos os Eventos</option>
                    <option v-for="type in eventTypes" :key="type" :value="type">{{ type }}</option>
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Status</label>
                <select v-model="filters.status" @change="fetchEvents(1)" class="w-full bg-gray-950 border border-gray-800 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500">
                    <option value="">Todos os Status</option>
                    <option value="pending">Pendente</option>
                    <option value="processed">Processado</option>
                    <option value="failed">Falha</option>
                    <option value="ignored">Ignorado</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-950/50 border-b border-gray-800 text-gray-400 text-xs uppercase tracking-wider font-bold">
                            <th class="px-6 py-4">Data</th>
                            <th class="px-6 py-4">Evento</th>
                            <th class="px-6 py-4">ID do Evento</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/50 text-sm">
                        <tr v-if="loading && events.length === 0" v-for="i in 5" :key="i" class="animate-pulse">
                            <td colspan="5" class="px-6 py-4"><div class="h-4 bg-gray-800 rounded w-full"></div></td>
                        </tr>
                        <tr v-else-if="events.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                <Search class="w-10 h-10 mx-auto mb-3 opacity-20" />
                                Nenhum evento encontrado.
                            </td>
                        </tr>
                        <tr v-for="event in events" :key="event.id" class="hover:bg-gray-800/30 transition-colors group">
                            <td class="px-6 py-4 text-gray-300 font-mono text-xs whitespace-nowrap">
                                {{ formatDate(event.created_at) }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-bold text-gray-100">{{ event.event_type }}</span>
                            </td>
                            <td class="px-6 py-4 text-gray-500 font-mono text-xs">
                                {{ event.event_id }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border" :class="getStatusBadgeClass(event.status)">
                                    <component :is="getStatusIcon(event.status)" class="w-3.5 h-3.5" />
                                    {{ event.status.toUpperCase() }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <button @click="viewDetail(event)" class="p-1.5 text-gray-400 hover:text-indigo-400 transition-colors" title="Ver Detalhes">
                                        <Eye class="w-4 h-4" />
                                    </button>
                                    <button @click="deleteEvent(event.id)" class="p-1.5 text-gray-400 hover:text-red-400 transition-colors" title="Excluir Log">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 bg-gray-950/50 border-t border-gray-800 flex items-center justify-between">
                <span class="text-xs text-gray-500">Total de <strong>{{ pagination.total }}</strong> notificações</span>
                <div class="flex gap-2">
                    <button 
                        @click="fetchEvents(pagination.current_page - 1)" 
                        :disabled="pagination.current_page <= 1"
                        class="p-2 border border-gray-800 rounded text-gray-400 hover:bg-gray-800 disabled:opacity-30 disabled:cursor-not-allowed"
                    >
                        <ChevronLeft class="w-4 h-4" />
                    </button>
                    <button 
                        @click="fetchEvents(pagination.current_page + 1)" 
                        :disabled="pagination.current_page >= pagination.last_page"
                        class="p-2 border border-gray-800 rounded text-gray-400 hover:bg-gray-800 disabled:opacity-30 disabled:cursor-not-allowed"
                    >
                        <ChevronRight class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Detail Modal -->
        <div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="bg-gray-900 border border-gray-800 rounded-2xl w-full max-w-3xl max-h-[80vh] flex flex-col shadow-2xl">
                <div class="p-6 border-b border-gray-800 flex justify-between items-center bg-gray-900/50">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-lg" :class="getStatusBadgeClass(selectedEvent.status)">
                            <Activity class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white">{{ selectedEvent.event_type }}</h2>
                            <p class="text-xs text-gray-500 font-mono">{{ selectedEvent.event_id }}</p>
                        </div>
                    </div>
                    <button @click="showDetailModal = false" class="text-gray-500 hover:text-white transition-colors">
                        <MinusCircle class="w-6 h-6 rotate-45" />
                    </button>
                </div>
                <div class="p-6 overflow-y-auto flex-1 space-y-6">
                    <div v-if="selectedEvent.error_message" class="bg-red-500/10 border border-red-500/20 p-4 rounded-xl">
                        <h4 class="text-xs font-bold text-red-400 uppercase mb-2">Mensagem de Erro:</h4>
                        <p class="text-sm text-red-200 font-mono">{{ selectedEvent.error_message }}</p>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold text-gray-500 uppercase mb-3 px-1">Carga de Dados (Payload Bruto):</h4>
                        <div class="bg-gray-950 rounded-xl border border-gray-800 p-4 overflow-x-auto shadow-inner">
                            <pre class="text-xs text-emerald-500 font-mono whitespace-pre-wrap leading-relaxed">{{ JSON.stringify(selectedEvent.payload, null, 2) }}</pre>
                        </div>
                    </div>
                </div>
                <div class="p-6 border-t border-gray-800 bg-gray-950/30 flex justify-end">
                    <button @click="showDetailModal = false" class="px-6 py-2 bg-gray-800 hover:bg-gray-700 text-white rounded-lg font-bold transition-all text-sm">
                        Fechar Detalhes
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
pre {
    scrollbar-width: thin;
    scrollbar-color: #312e81 #030712;
}
</style>
