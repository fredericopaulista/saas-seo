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

// Checkout Modal State
const showCheckoutModal = ref(false)
const selectedPlanId = ref<number | null>(null)
const selectedPlan = ref<any>(null)

// Payment Result Modal State
const showPaymentResult = ref(false)
const paymentResult = ref<{
  message: string
  paymentUrl: string | null
  pixData: { pixQrCode: string | null; pixCopiaECola: string | null; invoiceUrl: string | null } | null
  billingType: string
} | null>(null)

const checkout = ref({
    name: '',
    cpfCnpj: '',
    phone: '',
    billingType: 'PIX' as 'PIX' | 'CREDIT_CARD',
    // Credit Card fields
    cardHolder: '',
    cardNumber: '',
    cardExpiry: '',
    cardCvv: '',
})
const checkoutError = ref('')

const router = useRouter()

const openSubscribeModal = (planId: number) => {
    selectedPlanId.value = planId
    selectedPlan.value = plans.value.find((p) => p.id === planId)
    checkoutError.value = ''
    checkout.value = { name: '', cpfCnpj: '', phone: '', billingType: 'PIX', cardHolder: '', cardNumber: '', cardExpiry: '', cardCvv: '' }
    showCheckoutModal.value = true
}

const copyToClipboard = async (text: string) => {
    try {
        await navigator.clipboard.writeText(text)
        alert('Código PIX copiado!')
    } catch {
        // Fallback for older browsers
        const el = document.createElement('textarea')
        el.value = text
        document.body.appendChild(el)
        el.select()
        document.execCommand('copy')
        document.body.removeChild(el)
        alert('Código PIX copiado!')
    }
}

const closePaymentResult = () => {
    showPaymentResult.value = false
    paymentResult.value = null
    window.location.reload()
}

const confirmSubscription = async () => {
    checkoutError.value = ''

    if (!checkout.value.name.trim()) { checkoutError.value = 'Por favor, insira seu nome completo.'; return }
    const cpf = checkout.value.cpfCnpj.replace(/\D/g, '')
    if (cpf.length !== 11 && cpf.length !== 14) { checkoutError.value = 'CPF (11 dígitos) ou CNPJ (14 dígitos) inválido.'; return }
    if (!checkout.value.phone.trim()) { checkoutError.value = 'Por favor, insira seu telefone com DDD.'; return }

    if (checkout.value.billingType === 'CREDIT_CARD') {
        if (!checkout.value.cardHolder.trim()) { checkoutError.value = 'Insira o nome do titular do cartão.'; return }
        if (checkout.value.cardNumber.replace(/\D/g, '').length < 16) { checkoutError.value = 'Número do cartão inválido.'; return }
        if (!checkout.value.cardExpiry.trim()) { checkoutError.value = 'Insira a data de vencimento.'; return }
        if (checkout.value.cardCvv.length < 3) { checkoutError.value = 'Código de segurança inválido.'; return }
    }

    if (!selectedPlanId.value) return

    subscribingTo.value = selectedPlanId.value
    showCheckoutModal.value = false

    try {
        const payload: any = {
            plan_id: selectedPlanId.value,
            name: checkout.value.name.trim(),
            cpfCnpj: cpf,
            phone: checkout.value.phone.replace(/\D/g, ''),
            billingType: checkout.value.billingType,
        }

        if (checkout.value.billingType === 'CREDIT_CARD') {
            const [month, year] = checkout.value.cardExpiry.split('/')
            payload.creditCard = {
                holderName: checkout.value.cardHolder,
                number: checkout.value.cardNumber.replace(/\D/g, ''),
                expiryMonth: month,
                expiryYear: year?.length === 2 ? `20${year}` : year,
                ccv: checkout.value.cardCvv,
            }
            payload.creditCardHolderInfo = {
                name: checkout.value.name,
                cpfCnpj: cpf,
                phone: checkout.value.phone.replace(/\D/g, ''),
            }
        }

        const { data } = await api.post('/billing/subscribe', payload)

        // Redirect immediately if we have a payment URL (for credit card or external link)
        if (data.paymentUrl && checkout.value.billingType !== 'PIX') {
            window.location.href = data.paymentUrl
            return
        }

        // Show the result modal with PIX data or payment link
        paymentResult.value = {
            message: data.message,
            paymentUrl: data.paymentUrl,
            pixData: data.pixData ?? null,
            billingType: checkout.value.billingType,
        }
        showPaymentResult.value = true

    } catch (e: any) {
        // Re-open checkout modal with error on failure
        showCheckoutModal.value = true
        checkoutError.value = e.response?.data?.message || 'Falha ao processar assinatura. Tente novamente.'
    } finally {
        subscribingTo.value = null
        selectedPlanId.value = null
    }
}

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

    <!-- Full Checkout Modal -->
    <div v-if="showCheckoutModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
      <div class="bg-gray-900 border border-gray-800 rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl relative flex flex-col max-h-[90vh]">
        <!-- Accent Line -->
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-indigo-500 to-purple-500"></div>
        
        <!-- Header -->
        <div class="p-6 border-b border-gray-800 flex items-start justify-between">
          <div>
            <h3 class="text-xl font-bold text-white">Finalizar Assinatura</h3>
            <p class="text-sm text-gray-400 mt-1" v-if="selectedPlan">Plano <span class="text-indigo-400 font-semibold">{{ selectedPlan.name }}</span> · R$ {{ selectedPlan.price }}/{{ translateCycle(selectedPlan.billing_cycle) }}</p>
          </div>
          <button @click="showCheckoutModal = false" class="text-gray-500 hover:text-white transition-colors text-2xl leading-none">&times;</button>
        </div>

        <!-- Scrollable Body -->
        <div class="overflow-y-auto flex-1 p-6 space-y-5">

          <!-- Billing Info -->
          <div>
            <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3">Seus Dados</p>
            <div class="space-y-3">
              <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Nome Completo</label>
                <input v-model="checkout.name" type="text" placeholder="Frederico Moura" class="w-full bg-gray-950 border border-gray-800 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-sm" />
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-sm font-medium text-gray-300 mb-1">CPF ou CNPJ</label>
                  <input v-model="checkout.cpfCnpj" type="text" placeholder="000.000.000-00" class="w-full bg-gray-950 border border-gray-800 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all font-mono text-sm" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-300 mb-1">Telefone (DDD)</label>
                  <input v-model="checkout.phone" type="text" placeholder="(11) 99999-9999" class="w-full bg-gray-950 border border-gray-800 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-sm" />
                </div>
              </div>
            </div>
          </div>

          <!-- Payment Method Selector -->
          <div>
            <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3">Forma de Pagamento</p>
            <div class="grid grid-cols-2 gap-3">
              <button @click="checkout.billingType = 'PIX'"
                :class="checkout.billingType === 'PIX' ? 'border-indigo-500 bg-indigo-500/10 text-indigo-300' : 'border-gray-700 bg-gray-800 text-gray-400 hover:border-gray-600'"
                class="flex flex-col items-center gap-2 py-4 rounded-xl border transition-all font-medium text-sm">
                <span class="text-2xl">⚡</span>
                PIX
                <span class="text-xs font-normal opacity-70">Instantâneo</span>
              </button>
              <button @click="checkout.billingType = 'CREDIT_CARD'"
                :class="checkout.billingType === 'CREDIT_CARD' ? 'border-indigo-500 bg-indigo-500/10 text-indigo-300' : 'border-gray-700 bg-gray-800 text-gray-400 hover:border-gray-600'"
                class="flex flex-col items-center gap-2 py-4 rounded-xl border transition-all font-medium text-sm">
                <span class="text-2xl">💳</span>
                Cartão de Crédito
                <span class="text-xs font-normal opacity-70">Débito automático</span>
              </button>
            </div>
          </div>

          <!-- Credit Card Fields (conditional) -->
          <transition name="fade">
            <div v-if="checkout.billingType === 'CREDIT_CARD'" class="space-y-3">
              <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3">Dados do Cartão</p>
              <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Nome no Cartão</label>
                <input v-model="checkout.cardHolder" type="text" placeholder="FREDERICO MOURA" class="w-full bg-gray-950 border border-gray-800 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all font-mono text-sm uppercase" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Número do Cartão</label>
                <input v-model="checkout.cardNumber" type="text" placeholder="0000 0000 0000 0000" maxlength="19" class="w-full bg-gray-950 border border-gray-800 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all font-mono text-sm" />
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-sm font-medium text-gray-300 mb-1">Vencimento (MM/AA)</label>
                  <input v-model="checkout.cardExpiry" type="text" placeholder="12/27" maxlength="5" class="w-full bg-gray-950 border border-gray-800 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all font-mono text-sm" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-300 mb-1">CVV</label>
                  <input v-model="checkout.cardCvv" type="password" placeholder="•••" maxlength="4" class="w-full bg-gray-950 border border-gray-800 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all font-mono text-sm" />
                </div>
              </div>
            </div>
          </transition>

          <!-- Error message -->
          <div v-if="checkoutError" class="bg-red-500/10 border border-red-500/30 text-red-400 rounded-xl px-4 py-3 text-sm">
            {{ checkoutError }}
          </div>

        </div>

        <!-- Footer Actions -->
        <div class="p-6 border-t border-gray-800 flex gap-3">
          <button @click="showCheckoutModal = false" class="flex-1 py-3 rounded-xl font-bold text-gray-400 bg-gray-800 hover:bg-gray-700 hover:text-white transition-all text-sm border border-gray-700">
            Cancelar
          </button>
          <button @click="confirmSubscription" :disabled="subscribingTo !== null" class="flex-1 py-3 rounded-xl font-bold text-white bg-indigo-600 hover:bg-indigo-500 shadow-lg shadow-indigo-500/25 transition-all text-sm border border-indigo-500/50 flex items-center justify-center gap-2 disabled:opacity-50">
            <div v-if="subscribingTo !== null" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
            <span v-if="checkout.billingType === 'PIX'">Gerar QR Code PIX</span>
            <span v-else>Pagar com Cartão</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Processing overlay (after modal is closed, while waiting for API) -->
    <div v-if="subscribingTo !== null && !showCheckoutModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm">
      <div class="bg-gray-900 border border-gray-800 rounded-2xl p-10 text-center max-w-sm w-full mx-4 shadow-2xl">
        <div class="w-14 h-14 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin mx-auto mb-6"></div>
        <h3 class="text-lg font-bold text-white mb-2">Processando...</h3>
        <p class="text-gray-400 text-sm">Gerando sua assinatura no gateway de pagamento. Aguarde.</p>
      </div>
    </div>

    <!-- Payment Result Modal -->
    <div v-if="showPaymentResult && paymentResult" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
      <div class="bg-gray-900 border border-gray-800 rounded-2xl w-full max-w-md overflow-hidden shadow-2xl relative">
        <!-- Accent Line -->
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-green-500 to-emerald-400"></div>

        <div class="p-8 text-center">
          <!-- Success icon -->
          <div class="w-16 h-16 bg-green-500/10 border border-green-500/30 rounded-full flex items-center justify-center mx-auto mb-5">
            <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>

          <h3 class="text-xl font-bold text-white mb-2">Assinatura Criada!</h3>
          <p class="text-gray-400 text-sm mb-6">{{ paymentResult.message }}</p>

          <!-- PIX Data Display -->
          <div v-if="paymentResult.billingType === 'PIX'" class="space-y-4">
            <!-- QR Code image (if available) -->
            <div v-if="paymentResult.pixData?.pixQrCode" class="bg-white rounded-xl p-4 mx-auto inline-block">
              <img :src="'data:image/png;base64,' + paymentResult.pixData.pixQrCode" alt="QR Code PIX" class="w-48 h-48 object-contain mx-auto" />
            </div>

            <!-- Pix copia e cola -->
            <div v-if="paymentResult.pixData?.pixCopiaECola" class="bg-gray-950 border border-gray-700 rounded-xl p-4 text-left">
              <p class="text-xs text-gray-500 font-bold uppercase tracking-widest mb-2">PIX Copia e Cola</p>
              <p class="text-xs font-mono text-green-400 break-all leading-relaxed">{{ paymentResult.pixData.pixCopiaECola }}</p>
              <button 
                @click="copyToClipboard(paymentResult!.pixData!.pixCopiaECola!)"
                class="mt-3 w-full py-2 bg-green-500/10 hover:bg-green-500/20 text-green-400 rounded-lg text-xs font-bold border border-green-500/30 transition-all"
              >
                📋 Copiar Código PIX
              </button>
            </div>

            <!-- Fallback link to invoice -->
            <div v-if="paymentResult.pixData?.invoiceUrl || paymentResult.paymentUrl">
              <a 
                :href="paymentResult.pixData?.invoiceUrl || paymentResult.paymentUrl || '#'" 
                target="_blank"
                class="block w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold text-sm transition-all text-center"
              >
                🔗 Abrir Página de Pagamento
              </a>
            </div>

            <!-- No PIX data: just a message -->
            <div v-if="!paymentResult.pixData && !paymentResult.paymentUrl" class="bg-yellow-500/10 border border-yellow-500/30 rounded-xl p-4">
              <p class="text-yellow-400 text-sm">Sua assinatura foi criada. Você receberá as instruções de pagamento por e-mail em breve.</p>
            </div>
          </div>

          <!-- Credit Card / other result -->
          <div v-else>
            <div v-if="paymentResult.paymentUrl">
              <a 
                :href="paymentResult.paymentUrl" 
                target="_blank"
                class="block w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold text-sm transition-all text-center mb-3"
              >
                🔗 Acessar Página de Pagamento
              </a>
            </div>
            <div v-else class="bg-blue-500/10 border border-blue-500/30 rounded-xl p-4 mb-4">
              <p class="text-blue-400 text-sm">Assinatura ativada! Você receberá a confirmação por e-mail.</p>
            </div>
          </div>

          <button 
            @click="closePaymentResult"
            class="mt-4 w-full py-3 bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-white rounded-xl font-bold text-sm transition-all border border-gray-700"
          >
            Fechar e Atualizar
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s, transform 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(-8px); }
</style>
