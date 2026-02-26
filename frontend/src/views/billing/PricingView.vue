<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { Check } from 'lucide-vue-next'

const plans = ref<any[]>([])
const loading = ref(true)
const subscribingTo = ref<number | null>(null)
const currentSubscription = ref<any>(null)
const isCanceling = ref(false)

// CPF Modal State
const showCpfModal = ref(false)
const cpfInput = ref('')
const selectedPlanId = ref<number | null>(null)

const router = useRouter()

onMounted(async () => {
    try {
        const plansRes = await api.get('/billing/plans')
        plans.value = plansRes.data
        
        try {
            const subRes = await api.get('/billing/my-subscription')
            currentSubscription.value = subRes.data?.subscription || null
        } catch (subErr) {
            console.warn('No active subscription found or tenant missing:', subErr)
            currentSubscription.value = null
        }

    } catch (e) {
        console.error('Failed to load billing plans:', e)
    } finally {
        loading.value = false
    }
})

const openSubscribeModal = (planId: number) => {
    selectedPlanId.value = planId
    cpfInput.value = '' // reset input
    showCpfModal.value = true
}

const confirmSubscription = async () => {
    if (!cpfInput.value || cpfInput.value.length < 11) {
        alert('Por favor, insira um CPF ou CNPJ válido.')
        return
    }

    if (!selectedPlanId.value) return

    subscribingTo.value = selectedPlanId.value
    showCpfModal.value = false // hide modal while loading

    try {
        const { data } = await api.post('/billing/subscribe', {
            plan_id: selectedPlanId.value,
            cpfCnpj: cpfInput.value,
            billingType: 'CREDIT_CARD' // hardcoding test value for MVP bypass
        })
        
        // Se a API retornou o Link de Fatura PIX/Boleto do Asaas, você redireciona o cliente:
        if(data.paymentUrl) {
           window.location.href = data.paymentUrl;
        } else {
           alert(data.message)
           window.location.reload()
        }
        
    } catch (e: any) {
        alert(e.response?.data?.message || 'Falha ao processar assinatura.')
    } finally {
        subscribingTo.value = null
        selectedPlanId.value = null
    }
}

const cancelSubscription = async () => {
    if (!confirm('Tem certeza que deseja cancelar sua assinatura? O cancelamento é imediato e você perderá seus acessos estendidos.')) return;
    
    isCanceling.value = true
    try {
        const { data } = await api.post('/billing/cancel')
        alert(data.message || 'Assinatura cancelada com sucesso.')
        window.location.reload()
    } catch (e: any) {
        alert(e.response?.data?.message || 'Falha ao processar cancelamento.')
    } finally {
        isCanceling.value = false
    }
}

const translateCycle = (cycle: string) => {
    const map: Record<string, string> = {
        'MONTHLY': 'mês',
        'BIMONTHLY': 'bimestre',
        'QUARTERLY': 'trimestre',
        'SEMIANNUALLY': 'semestre',
        'YEARLY': 'ano'
    }
    return map[cycle] || 'mês'
}
</script>

<template>
  <div class="min-h-screen bg-gray-900 text-white flex flex-col pt-16">
    <div class="max-w-6xl mx-auto px-4 w-full">
      <div class="text-center max-w-2xl mx-auto mb-12">
        <h1 class="text-4xl font-bold mb-4">Gerencie sua Assinatura</h1>
        <p class="text-xl text-gray-400">Desde consultores independentes até operações enterprise de SEO.</p>
      </div>

      <div v-if="loading" class="flex justify-center my-20">
          <div class="w-10 h-10 border-4 border-red-500 border-t-transparent rounded-full animate-spin"></div>
      </div>

      <div v-else>
        
        <!-- Current Subscription Box -->
        <div v-if="currentSubscription" class="bg-gray-800 border border-gray-700 rounded-2xl p-6 mb-12 flex justify-between items-center max-w-3xl mx-auto">
            <div>
                <p class="text-sm text-gray-400 uppercase tracking-widest font-bold mb-1">Seu Plano Atual</p>
                <h3 class="text-2xl font-bold text-white">{{ currentSubscription.plan.name }}</h3>
                <p class="text-gray-400 mt-2">
                    Gateway Status: <span class="text-indigo-400 font-semibold">{{ currentSubscription.status_gateway }}</span>
                </p>
            </div>
            <div>
                <button 
                    @click="cancelSubscription" 
                    :disabled="isCanceling"
                    class="bg-red-500/10 hover:bg-red-500/20 text-red-500 px-6 py-3 rounded-lg font-bold transition-all text-sm flex items-center justify-center gap-2"
                >
                    <div v-if="isCanceling" class="w-4 h-4 border-2 border-red-500/30 border-t-red-500 rounded-full animate-spin"></div>
                    Cancelar Assinatura
                </button>
            </div>
        </div>
        
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        
        <div v-for="plan in plans" :key="plan.id" class="bg-gray-950 border border-gray-800 rounded-2xl p-8 flex flex-col relative transition-transform hover:-translate-y-2" :class="{ 'ring-2 ring-indigo-500': currentSubscription && currentSubscription.plan_id === plan.id }">
            
            <div v-if="plan.slug === 'pro' && (!currentSubscription || currentSubscription.plan_id !== plan.id)" class="absolute -top-4 left-1/2 -translate-x-1/2 bg-gradient-to-r from-red-500 to-purple-500 text-white px-4 py-1 text-xs font-bold rounded-full">
                MAIS POPULAR
            </div>

            <div v-if="currentSubscription && currentSubscription.plan_id === plan.id" class="absolute -top-4 left-1/2 -translate-x-1/2 bg-indigo-600 text-white px-4 py-1 text-xs font-bold rounded-full">
                PLANO ATUAL
            </div>

            <h3 class="text-2xl font-bold mb-2">{{ plan.name }}</h3>
            <div class="flex items-baseline gap-1 mb-6">
                <span class="text-4xl font-bold">R$ {{ plan.price }}</span>
                <span class="text-gray-400">/{{ translateCycle(plan.billing_cycle) }}</span>
            </div>
            
            <p class="text-sm text-gray-400 mb-6">Limite de {{ plan.max_projects }} propriedades do GSC indexadas simultâneamente.</p>
            
            <ul class="space-y-4 mb-8 flex-1">
                <li v-for="(feature, index) in plan.features_json" :key="index" class="flex items-start gap-3">
                    <Check class="w-5 h-5 text-green-500 flex-shrink-0" />
                    <span class="text-gray-300 text-sm">{{ feature }}</span>
                </li>
            </ul>

            <button 
              @click="openSubscribeModal(plan.id)" 
              :disabled="subscribingTo === plan.id || (currentSubscription && currentSubscription.plan_id === plan.id)"
              class="w-full py-4 rounded-xl font-bold transition-all text-sm flex items-center justify-center gap-2"
              :class="[
                  (currentSubscription && currentSubscription.plan_id === plan.id)
                  ? 'bg-gray-800 text-gray-400 cursor-not-allowed'
                  : plan.slug === 'pro' ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-indigo-600 hover:bg-indigo-700 text-white'
              ]"
            >
                <div v-if="subscribingTo === plan.id" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                
                <span v-if="currentSubscription && currentSubscription.plan_id === plan.id">Plano Vigente</span>
                <span v-else-if="currentSubscription && plan.price > currentSubscription.plan.price">Fazer Upgrade</span>
                <span v-else-if="currentSubscription && plan.price < currentSubscription.plan.price">Fazer Downgrade</span>
                <span v-else>Assinar {{ plan.name }}</span>
            </button>
        </div>

      </div>
      </div>
    </div>

    <!-- CPF/CNPJ Checkout Modal -->
    <div v-if="showCpfModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
      <div class="bg-gray-900 border border-gray-800 rounded-2xl w-full max-w-md overflow-hidden shadow-2xl relative">
        <!-- Accent Line -->
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-indigo-500 to-purple-500"></div>
        
        <div class="p-6">
          <h3 class="text-xl font-bold text-white mb-2">Detalhes de Faturamento</h3>
          <p class="text-sm text-gray-400 mb-6">
            O Asaas, nosso gateway e parceiro financeiro oficial, exige um documento válido brasileiro (CPF ou CNPJ) associado ao titular da cobrança.
          </p>
          
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-300 mb-2">CPF ou CNPJ (apenas números)</label>
            <input 
                v-model="cpfInput" 
                type="text" 
                placeholder="Ex: 12345678900" 
                class="w-full bg-gray-950 border border-gray-800 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all font-mono"
                @keyup.enter="confirmSubscription"
            >
          </div>

          <div class="flex gap-3">
            <button 
                @click="showCpfModal = false" 
                class="flex-1 py-3 rounded-xl font-bold text-gray-400 bg-gray-800 hover:bg-gray-700 hover:text-white transition-all text-sm border border-gray-700"
            >
                Cancelar
            </button>
            <button 
                @click="confirmSubscription" 
                class="flex-1 py-3 rounded-xl font-bold text-white bg-indigo-600 hover:bg-indigo-500 shadow-lg shadow-indigo-500/25 transition-all text-sm border border-indigo-500/50"
            >
                Continuar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
