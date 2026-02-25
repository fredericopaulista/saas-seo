<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import { CreditCard, ArrowUpRight, CheckCircle2, XCircle, AlertCircle } from 'lucide-vue-next'

const loading = ref(true)
const payload = ref({
    plans: [] as any[],
    subscriptions: [] as any[]
})

onMounted(async () => {
    try {
        const { data } = await api.get('/admin/billing-overview')
        payload.value = data
    } catch (e) {
        console.error('Failed to load admin billing data')
    } finally {
        loading.value = false
    }
})

const formatCurrency = (val: number) => {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val)
}

const statusColor = (status: string) => {
    if (status === 'ACTIVE' || status === 'active') return 'text-green-500 bg-green-500/10'
    if (status === 'PENDING' || status === 'pending') return 'text-orange-500 bg-orange-500/10'
    return 'text-red-500 bg-red-500/10'
}

const statusIcon = (status: string) => {
    if (status === 'ACTIVE' || status === 'active') return CheckCircle2
    if (status === 'PENDING' || status === 'pending') return AlertCircle
    return XCircle
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
      <!-- Plans Active Subscriptions -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div v-for="plan in payload.plans" :key="plan.id" class="bg-gray-900 border border-gray-800 rounded-xl p-6 relative overflow-hidden">
          <div class="flex justify-between items-start mb-4">
            <h3 class="font-bold text-lg text-white">{{ plan.name }}</h3>
            <span class="px-2 py-1 bg-gray-800 rounded text-xs text-gray-400">R$ {{ plan.price }}</span>
          </div>
          <div class="mt-4">
            <div class="text-3xl font-bold text-indigo-400">{{ plan.subscriptions_count }}</div>
            <p class="text-sm text-gray-500">Assinaturas Ativas</p>
          </div>
          <div class="absolute -bottom-4 -right-4 opacity-5">
            <CreditCard class="w-32 h-32" />
          </div>
        </div>
      </div>

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
              <th class="px-6 py-4 font-medium">Status Gateway</th>
              <th class="px-6 py-4 font-medium">Asaas ID</th>
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
              <td class="px-6 py-4">
                  <span :class="statusColor(sub.status_gateway || sub.status)" class="px-2 py-1 rounded inline-flex items-center gap-1.5 text-xs font-medium uppercase">
                      <component :is="statusIcon(sub.status_gateway || sub.status)" class="w-3 h-3" />
                      {{ sub.status_gateway || sub.status }}
                  </span>
              </td>
              <td class="px-6 py-4 font-mono text-xs text-gray-500">
                  {{ sub.asaas_subscription_id || 'Aguardando Sinc...' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </div>
</template>
