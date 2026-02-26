<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { Check, ArrowRight, Zap, Target, BarChart3, Database } from 'lucide-vue-next'

const plans = ref<any[]>([])
const loading = ref(true)
const router = useRouter()

onMounted(async () => {
    try {
        const plansRes = await api.get('/billing/plans')
        plans.value = plansRes.data
    } catch (e) {
        console.error('Failed to load billing plans:', e)
    } finally {
        loading.value = false
    }
})

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

const ctaClick = (planSlug: string) => {
    // Redirect to register, could pass plan intent in query if desired
    router.push({ name: 'register', query: { plan: planSlug } })
}
</script>

<template>
  <div class="min-h-screen bg-[#050505] text-white selection:bg-indigo-500 selection:text-white font-sans overflow-x-hidden">
    
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-[#050505]/80 backdrop-blur-lg border-b border-white/10">
      <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-indigo-500 to-purple-500 flex items-center justify-center">
                <Target class="w-5 h-5 text-white" />
            </div>
            <span class="text-xl font-bold tracking-tight">SaaS SEO</span>
        </div>
        <div class="flex items-center gap-4">
            <router-link to="/login" class="text-sm font-medium text-gray-400 hover:text-white transition-colors">Entrar</router-link>
            <router-link to="/register" class="text-sm font-bold bg-white text-black px-5 py-2.5 rounded-full hover:bg-gray-200 transition-colors">
                Começar Grátis
            </router-link>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative pt-40 pb-24 lg:pt-56 lg:pb-32 px-6">
        <!-- Abstract gradient mesh -->
        <div class="absolute inset-0 pointer-events-none flex items-center justify-center overflow-hidden">
            <div class="w-[800px] h-[800px] bg-indigo-500/20 rounded-full blur-[120px] absolute top-[-200px] opacity-50 mix-blend-screen"></div>
            <div class="w-[600px] h-[600px] bg-purple-500/20 rounded-full blur-[100px] absolute bottom-[-100px] opacity-40 mix-blend-screen"></div>
        </div>

        <div class="relative z-10 max-w-5xl mx-auto text-center">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/5 border border-white/10 text-xs font-semibold text-indigo-300 mb-8 uppercase tracking-widest backdrop-blur-md">
                <Zap class="w-4 h-4" /> Indexação Escalável V2.0
            </div>
            <h1 class="text-5xl lg:text-7xl font-extrabold tracking-tight leading-[1.1] mb-8 bg-gradient-to-b from-white to-gray-400 bg-clip-text text-transparent">
                Domine o Google.<br />No piloto automático.
            </h1>
            <p class="text-xl text-gray-400 max-w-3xl mx-auto leading-relaxed mb-12">
                Conecte o Google Search Console, monitore centenas de propriedades simultaneamente e automatize a indexação de milhares de URLs todos os dias via API Oficial. A vantagem técnica que a sua agência precisava.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <button @click="router.push('/register')" class="h-14 px-8 bg-indigo-600 hover:bg-indigo-500 text-white rounded-full font-bold text-lg transition-all flex items-center gap-2 shadow-[0_0_40px_-10px_rgba(99,102,241,0.6)] hover:shadow-[0_0_60px_-15px_rgba(99,102,241,0.8)] hover:-translate-y-1">
                    Automatizar Indexação Agora <ArrowRight class="w-5 h-5" />
                </button>
            </div>
            <p class="mt-6 text-sm text-gray-500 font-medium">✨ Teste 1 projeto gratuito. Sem cartão de crédito inicial.</p>
        </div>
    </section>

    <!-- Bento Grid Features -->
    <section class="py-24 px-6 relative border-t border-white/5 bg-[#0a0a0a]">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-3xl lg:text-4xl font-bold mb-4">Construído para Operações Robustas</h2>
                <p class="text-gray-400 text-lg">Esqueça scripts locais e sitemaps lentos. O poder da Indexação Direta.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 grid-rows-2 gap-6 h-auto md:h-[600px]">
                <!-- Feature 1 -->
                <div class="md:col-span-2 md:row-span-1 rounded-3xl bg-white/[0.02] border border-white/[0.05] p-8 flex flex-col justify-between overflow-hidden relative group hover:bg-white/[0.04] transition-colors">
                    <div class="absolute right-0 top-0 w-64 h-64 bg-indigo-500/10 blur-[80px] group-hover:bg-indigo-500/20 transition-all"></div>
                    <div class="relative z-10 w-12 h-12 rounded-xl bg-indigo-500/20 flex items-center justify-center mb-6">
                        <Database class="w-6 h-6 text-indigo-400" />
                    </div>
                    <div class="relative z-10">
                        <h3 class="text-2xl font-bold mb-3">Multi-Tennant Integrado</h3>
                        <p class="text-gray-400 leading-relaxed max-w-md">Gerencie até 5.000 propriedades do Search Console em uma única tela. Troque de projeto em milissegundos sem perder o contexto analítico de cada domínio.</p>
                    </div>
                </div>

                <!-- Feature 2 -->
                <div class="md:col-span-1 md:row-span-2 rounded-3xl bg-gradient-to-b from-indigo-900/40 to-transparent border border-indigo-500/20 p-8 flex flex-col items-center text-center justify-center relative overflow-hidden group">
                    <div class="w-20 h-20 rounded-full bg-indigo-500/20 flex items-center justify-center mb-8 shadow-[0_0_50px_rgba(99,102,241,0.3)]">
                        <Zap class="w-10 h-10 text-indigo-300" />
                    </div>
                    <h3 class="text-2xl font-bold mb-4">200 URLs por Dia</h3>
                    <p class="text-indigo-200/70 leading-relaxed">
                        Desbloqueie o bypass Oficial do Google Cloud. Empurre via API até 200 novas páginas diariamente para o index do Google por projeto conectado.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="md:col-span-2 md:row-span-1 rounded-3xl bg-white/[0.02] border border-white/[0.05] p-8 flex flex-col justify-between overflow-hidden relative group hover:bg-white/[0.04] transition-colors">
                     <div class="absolute bottom-0 left-0 w-64 h-64 bg-purple-500/10 blur-[80px] group-hover:bg-purple-500/20 transition-all"></div>
                     <div class="relative z-10 w-12 h-12 rounded-xl bg-purple-500/20 flex items-center justify-center mb-6">
                        <BarChart3 class="w-6 h-6 text-purple-400" />
                    </div>
                    <div class="relative z-10">
                        <h3 class="text-2xl font-bold mb-3">Auditoria Visual e Métricas</h3>
                        <p class="text-gray-400 leading-relaxed max-w-md">Painel em tempo real detectando Anomalias de CTR, Picos de Impressão e páginas que perderam tração nas SERPs nos últimos 28 dias.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="py-24 px-6 bg-[#050505] relative z-10" id="pricing">
      <div class="max-w-6xl mx-auto">
        <div class="text-center max-w-2xl mx-auto mb-20">
          <h2 class="text-3xl lg:text-5xl font-bold mb-6">Escale seu tráfego, não seus custos.</h2>
          <p class="text-xl text-gray-400">Planos flexíveis atrelados ao limite da sua operação de SEO.</p>
        </div>

        <div v-if="loading" class="flex justify-center my-20">
            <div class="w-10 h-10 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div v-for="plan in plans" :key="plan.id" class="rounded-3xl p-8 flex flex-col relative transition-transform hover:-translate-y-2" :class="[plan.slug === 'pro' ? 'bg-gradient-to-b from-[#1a1a2e] to-[#0a0a10] border border-indigo-500/50 shadow-[0_0_50px_-15px_rgba(99,102,241,0.3)]' : 'bg-white/[0.02] border border-white/[0.05]']">
                
                <div v-if="plan.slug === 'pro'" class="absolute -top-4 left-1/2 -translate-x-1/2 bg-gradient-to-r from-indigo-500 to-purple-500 text-white px-4 py-1 text-xs font-bold rounded-full tracking-wide">
                    MAIS POPULAR
                </div>

                <h3 class="text-2xl font-bold mb-2">{{ plan.name }}</h3>
                <div class="flex items-baseline gap-1 mb-6">
                    <span class="text-4xl font-extrabold text-white">R$ {{ plan.price }}</span>
                    <span class="text-gray-500 font-medium">/{{ translateCycle(plan.billing_cycle) }}</span>
                </div>
                
                <p class="text-sm border-b border-white/10 pb-6 mb-6 font-medium" :class="plan.slug === 'pro' ? 'text-indigo-200' : 'text-gray-400'">Até {{ plan.max_projects }} projetos simultâneos (GSC).</p>
                
                <ul class="space-y-4 mb-10 flex-1">
                    <li v-for="(feature, index) in plan.features_json" :key="index" class="flex items-start gap-4">
                        <div class="w-5 h-5 rounded flex items-center justify-center flex-shrink-0 mt-0.5" :class="plan.slug === 'pro' ? 'bg-indigo-500/20' : 'bg-white/10'">
                            <Check class="w-3.5 h-3.5" :class="plan.slug === 'pro' ? 'text-indigo-400' : 'text-white'" />
                        </div>
                        <span class="text-gray-300 text-sm leading-relaxed font-medium">{{ feature }}</span>
                    </li>
                </ul>

                <button 
                @click="ctaClick(plan.slug)" 
                class="w-full py-4 rounded-2xl font-bold transition-all text-sm tracking-wide"
                :class="[
                    plan.slug === 'pro' ? 'bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg' : 'bg-white/10 hover:bg-white/20 text-white'
                ]"
                >
                    Assinar {{ plan.name }}
                </button>
            </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="py-12 px-6 border-t border-white/10 bg-[#020202]">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-6">
             <div class="flex items-center gap-2">
                <Target class="w-5 h-5 text-gray-500" />
                <span class="font-bold text-gray-400 tracking-tight text-lg">SaaS SEO</span>
            </div>
            <p class="text-gray-500 text-sm">© {{ new Date().getFullYear() }} SaaS SEO Automations. <span class="mx-2">&bull;</span> <a href="https://fredericomoura.com" title="Frederico Moura" target="_blank" class="hover:text-white transition-colors">Feito por Frederico Moura</a></p>
        </div>
    </footer>

  </div>
</template>
