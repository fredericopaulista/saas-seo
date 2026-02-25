<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { Check } from 'lucide-vue-next'

const plans = ref<any[]>([])
const loading = ref(true)
const subscribingTo = ref<number | null>(null)

const router = useRouter()

onMounted(async () => {
    try {
        const { data } = await api.get('/billing/plans')
        plans.value = data
    } catch (e) {
        console.error('Failed to load plans:', e)
    } finally {
        loading.value = false
    }
})

const subscribe = async (planId: number) => {
    subscribingTo.value = planId
    try {
        const { data } = await api.post('/billing/subscribe', {
            plan_id: planId,
            billingType: 'CREDIT_CARD' // hardcoding test value for MVP bypass
        })
        
        // Se a API retornou o Link de Fatura PIX/Boleto do Asaas, você redireciona o cliente:
        if(data.paymentUrl) {
           window.location.href = data.paymentUrl;
        } else {
           alert(data.message)
           router.push('/dashboard')
        }
        
    } catch (e: any) {
        alert(e.response?.data?.message || 'Falha ao processar assinatura.')
    } finally {
        subscribingTo.value = null
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
      <div class="text-center max-w-2xl mx-auto mb-16">
        <h1 class="text-4xl font-bold mb-4">Escolha a escala da sua Agência</h1>
        <p class="text-xl text-gray-400">Desde consultores independentes até operações enterprise de SEO.</p>
      </div>

      <div v-if="loading" class="flex justify-center my-20">
          <div class="w-10 h-10 border-4 border-red-500 border-t-transparent rounded-full animate-spin"></div>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-8">
        
        <div v-for="plan in plans" :key="plan.id" class="bg-gray-950 border border-gray-800 rounded-2xl p-8 flex flex-col relative transition-transform hover:-translate-y-2">
            
            <div v-if="plan.slug === 'pro'" class="absolute -top-4 left-1/2 -translate-x-1/2 bg-gradient-to-r from-red-500 to-purple-500 text-white px-4 py-1 text-xs font-bold rounded-full">
                MAIS POPULAR
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
              @click="subscribe(plan.id)" 
              :disabled="subscribingTo === plan.id"
              class="w-full py-4 rounded-xl font-bold transition-all text-sm flex items-center justify-center gap-2"
              :class="plan.slug === 'pro' ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-gray-800 hover:bg-gray-700 text-white'"
            >
                <div v-if="subscribingTo === plan.id" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                Garantir {{ plan.name }}
            </button>
        </div>

      </div>
    </div>
  </div>
</template>
