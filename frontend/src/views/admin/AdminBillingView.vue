<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import { useUIStore } from '@/stores/ui'
import { CreditCard, ArrowUpRight, CheckCircle2, XCircle, AlertCircle, Clock, Ban, RotateCcw } from 'lucide-vue-next'
import { format } from 'date-fns'
import { ptBR } from 'date-fns/locale'

const loading = ref(true)
const payload = ref({
    plans: [] as any[],
    subscriptions: [] as any[]
})
const processingId = ref<number | null>(null)
const uiStore = useUIStore()

const fetchBillingData = async () => {
    loading.value = true
    try {
        const { data } = await api.get('/admin/billing-overview')
        payload.value = data
    } catch (e) {
        console.error('Failed to load admin billing data')
    } finally {
        loading.value = false
    }
}

onMounted(async () => {
    fetchBillingData()
})

const formatCurrency = (val: number) => {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val)
}

const statusColor = (status: string) => {
    const s = (status || '').toUpperCase()
    if (['ACTIVE', 'CONFIRMED', 'RECEIVED'].includes(s)) return 'text-green-500 bg-green-500/10'
    if (['PENDING'].includes(s)) return 'text-orange-500 bg-orange-500/10'
    if (['REFUNDED', 'CANCELED', 'CANCELLED'].includes(s)) return 'text-gray-500 bg-gray-500/10 border-gray-800'
    return 'text-red-500 bg-red-500/10'
}

const translateStatus = (status: string) => {
    const map: any = {
        'ACTIVE': 'Ativo',
        'PENDING': 'Pendente',
        'OVERDUE': 'Vencido',
        'EXPIRED': 'Expirado',
        'CANCELED': 'Cancelado',
        'CANCELLED': 'Cancelado',
        'REFUNDED': 'Estornado',
        'RECEIVED': 'Recebido',
        'CONFIRMED': 'Confirmado',
        'DELETED': 'Excluído',
        'Aguardando Sinc...': 'Aguardando Sinc...'
    }
    return map[status.toUpperCase()] || status
}

const statusIcon = (status: string) => {
    const s = (status || '').toUpperCase()
    if (['ACTIVE', 'CONFIRMED', 'RECEIVED'].includes(s)) return CheckCircle2
    if (['PENDING'].includes(s)) return AlertCircle
    if (['CANCELED', 'CANCELLED', 'REFUNDED'].includes(s)) return Ban
    return XCircle
}

const formatDate = (date: string) => {
    if (!date) return '-'
    return format(new Date(date), 'dd/MM/yyyy HH:mm', { locale: ptBR })
}

const cancelSubscription = async (id: number) => {
    const confirmed = await uiStore.confirm({
        title: 'Cancelar Assinatura',
        message: 'Deseja realmente CANCELAR esta assinatura no Asaas? Esta ação interromperá as cobranças futuras.',
        confirmText: 'Sim, Cancelar',
        type: 'danger'
    })
    
    if (!confirmed) return
    
    processingId.value = id
    try {
        await api.post(`/admin/subscriptions/${id}/cancel`)
        uiStore.addToast('Assinatura cancelada com sucesso.', 'success')
        fetchBillingData()
    } catch (e: any) {
        uiStore.addToast(e.response?.data?.error || 'Erro ao cancelar assinatura.', 'error')
    } finally {
        processingId.value = null
    }
}

const refundSubscription = async (id: number) => {
    const confirmed = await uiStore.confirm({
        title: 'Estornar Pagamento',
        message: 'Deseja realmente ESTORNAR o último pagamento desta venda no Asaas? O valor será devolvido ao cliente.',
        confirmText: 'Sim, Estornar',
        type: 'warning'
    })
    
    if (!confirmed) return

    processingId.value = id
    try {
        await api.post(`/admin/subscriptions/${id}/refund`)
        uiStore.addToast('Solicitação de estorno enviada com sucesso ao Asaas.', 'success')
        fetchBillingData()
    } catch (e: any) {
        uiStore.addToast(e.response?.data?.error || 'Erro ao processar estorno.', 'error')
    } finally {
        processingId.value = null
    }
}

</script>

<template>
  <div class="space-y-8">
    
    <div>
      <h1 class="text-3xl font-bold tracking-tight text-white flex items-center gap-3">
        <CreditCard class="w-8 h-8 text-indigo-500" />
        Gestão de Faturamento (Gateway)
      </h1>
      <p class="text-gray-400 mt-2">Controle central dos Planos cadastrados e histórico de Faturas dos Inquilinos via Asaas.</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center p-20">
      <div class="w-8 h-8 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <template v-else>

      <!-- Recent Subscriptions Ledger -->
      <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden mt-8">
        <div class="px-6 py-4 border-b border-gray-800 bg-gray-950/50">
            <h3 class="font-medium text-white flex justify-between items-center">
                Livro Razão de Assinaturas (Últimas Transações)
                <button class="text-sm text-indigo-400 hover:text-indigo-300 flex items-center gap-1">
                    Exportar Relatório <ArrowUpRight class="w-4 h-4" />
                </button>
            </h3>
        </div>
        
        <table class="w-full text-left">
          <thead class="bg-gray-950/20 text-xs text-gray-400">
            <tr>
              <th class="px-6 py-4 font-medium">Inquilino</th>
              <th class="px-6 py-4 font-medium">Plano Contratado</th>
              <th class="px-6 py-4 font-medium">Ciclo</th>
              <th class="px-6 py-4 font-medium">Assinado em</th>
              <th class="px-6 py-4 font-medium">Status Gateway</th>
              <th class="px-6 py-4 font-medium">Asaas ID</th>
              <th class="px-6 py-4 font-medium text-right">Ações de Root</th>
            </tr>
          </thead>
          <tbody class="text-sm text-gray-300">
            <tr v-if="payload.subscriptions.length === 0">
                <td colspan="5" class="px-6 py-12 text-center text-gray-500">Nenhuma transação financeira registrada no Gateway ainda.</td>
            </tr>
            <tr v-for="sub in payload.subscriptions" :key="sub.id" class="border-t border-gray-800 hover:bg-gray-800/30">
              <td class="px-6 py-4 font-medium text-white">{{ sub.tenant ? sub.tenant.name : 'Unknown' }}</td>
              <td class="px-6 py-4">
                 <span class="text-indigo-400 font-medium">{{ sub.plan ? sub.plan.name : 'Custom' }}</span>
              </td>
              <td class="px-6 py-4 text-gray-500">Mensal</td>
              <td class="px-6 py-4 text-gray-400 font-mono text-xs">
                  {{ formatDate(sub.created_at) }}
              </td>
              <td class="px-6 py-4">
                  <span :class="statusColor(sub.status_gateway || sub.status)" class="px-2 py-1 rounded inline-flex items-center gap-1.5 text-xs font-medium uppercase border border-transparent">
                      <component :is="statusIcon(sub.status_gateway || sub.status)" class="w-3 h-3" />
                      {{ translateStatus(sub.status_gateway || sub.status) }}
                  </span>
              </td>
              <td class="px-6 py-4 font-mono text-xs text-gray-500">
                  {{ sub.asaas_subscription_id || 'Aguardando Sinc...' }}
              </td>
              <td class="px-6 py-4 text-right">
                  <div class="flex justify-end gap-2">
                      <button 
                         @click="refundSubscription(sub.id)" 
                         :disabled="processingId === sub.id"
                         class="p-1.5 text-gray-400 hover:text-orange-400 transition-colors disabled:opacity-30" 
                         title="Estornar Venda"
                      >
                          <RotateCcw class="w-4 h-4" :class="{ 'animate-spin': processingId === sub.id }" />
                      </button>
                      <button 
                         @click="cancelSubscription(sub.id)" 
                         :disabled="processingId === sub.id || (sub.status_gateway || sub.status) === 'CANCELED'"
                         class="p-1.5 text-gray-400 hover:text-red-500 transition-colors disabled:opacity-30" 
                         title="Cancelar Assinatura"
                      >
                          <Ban class="w-4 h-4" />
                      </button>
                  </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </div>
</template>
