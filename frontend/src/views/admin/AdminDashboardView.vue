<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import { TrendingUp, Users, Building, Activity } from 'lucide-vue-next'

const loading = ref(true)
const metrics = ref({
    mrr: 0,
    arr: 0,
    churn_rate: 0,
    total_users: 0,
    total_tenants: 0,
    total_projects: 0
})

onMounted(async () => {
    try {
        const { data } = await api.get('/admin/dashboard')
        metrics.value = data
    } catch (e) {
        console.error('Failed to load global metrics')
    } finally {
        loading.value = false
    }
})

const formatCurrency = (val: number) => {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val)
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-3xl font-bold tracking-tight text-white">SaaS Overview</h1>
      <p class="text-gray-400 mt-2">Visão Executiva de performance e saúde da plataforma.</p>
    </div>

    <!-- Metrics Grid -->
    <div v-if="!loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      
      <!-- MRR Card -->
      <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 relative overflow-hidden group">
        <div class="absolute right-0 top-0 opacity-10 transform translate-x-4 -translate-y-4 group-hover:scale-110 transition-transform">
          <TrendingUp class="w-32 h-32" />
        </div>
        <div class="flex items-center space-x-4 mb-4">
          <div class="p-3 bg-red-500/10 text-red-500 rounded-lg">
            <TrendingUp class="w-6 h-6" />
          </div>
          <h3 class="text-gray-400 font-medium">Monthly Recurring Rev</h3>
        </div>
        <div class="text-3xl font-bold text-white">{{ formatCurrency(metrics.mrr) }}</div>
        <div class="mt-2 text-sm text-green-500 flex items-center">
            +12.5% vs último mês
        </div>
      </div>

      <!-- ARR Card -->
      <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 relative flex flex-col justify-between">
        <div class="flex items-center space-x-4 mb-4">
          <div class="p-3 bg-purple-500/10 text-purple-500 rounded-lg">
            <Activity class="w-6 h-6" />
          </div>
          <h3 class="text-gray-400 font-medium">Annual Recurring Rev</h3>
        </div>
        <div class="text-3xl font-bold text-white">{{ formatCurrency(metrics.arr) }}</div>
      </div>

      <!-- Tenants Card -->
      <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <div class="flex items-center space-x-4 mb-4">
          <div class="p-3 bg-blue-500/10 text-blue-500 rounded-lg">
            <Building class="w-6 h-6" />
          </div>
          <h3 class="text-gray-400 font-medium">Total de Tenants</h3>
        </div>
        <div class="text-3xl font-bold text-white">{{ metrics.total_tenants }}</div>
      </div>

      <!-- Users Card -->
      <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <div class="flex items-center space-x-4 mb-4">
          <div class="p-3 bg-orange-500/10 text-orange-500 rounded-lg">
            <Users class="w-6 h-6" />
          </div>
          <h3 class="text-gray-400 font-medium">Usuários Ativos</h3>
        </div>
        <div class="text-3xl font-bold text-white">{{ metrics.total_users }}</div>
      </div>

    </div>

    <!-- Skeleton Loader -->
    <div v-else class="grid grid-cols-1 md:grid-cols-4 gap-6 animate-pulse">
        <div v-for="i in 4" :key="i" class="h-32 bg-gray-900 rounded-xl border border-gray-800"></div>
    </div>
  </div>
</template>
