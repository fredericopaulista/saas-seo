<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { Building, ShieldAlert, LogIn } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const tenants = ref<any[]>([])
const loading = ref(true)

onMounted(async () => {
    try {
        const { data } = await api.get('/admin/tenants')
        tenants.value = data.tenants
    } catch (e) {
        console.error('Failed fetching tenants')
    } finally {
        loading.value = false
    }
})

const impersonate = async (tenantId: number) => {
    try {
        const { data } = await api.post(`/admin/tenants/${tenantId}/impersonate`)
        
        // Save original admin token to return later? Not spec'd right now
        // Overwrite user session with the impersonated token to fly to their dashboard
        localStorage.setItem('auth_token', data.impersonation_token)
        authStore.token = data.impersonation_token
        await authStore.fetchUser()
        
        router.push('/dashboard')
    } catch (e) {
        alert('Impersonation Failed')
    }
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold tracking-tight text-white">Gestão de Locatários</h1>
        <p class="text-gray-400 mt-2">Lista completa de todos os Tenants do sistema (Workspaces B2B).</p>
      </div>
    </div>

    <div class="bg-gray-900 rounded-xl border border-gray-800 overflow-hidden">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-gray-950/50 border-b border-gray-800 text-gray-400 text-sm">
            <th class="py-4 px-6 font-medium">Workspace (Tenant)</th>
            <th class="py-4 px-6 font-medium">Plano / Billing</th>
            <th class="py-4 px-6 font-medium">Criado em</th>
            <th class="py-4 px-6 font-medium text-right">Ações Administrativas</th>
          </tr>
        </thead>
        <tbody>
          <!-- Empty State -->
          <tr v-if="tenants.length === 0 && !loading">
            <td colspan="4" class="py-12 text-center text-gray-500">
                <Building class="w-12 h-12 mx-auto mb-3 opacity-20" />
                Nenhum Workspace encontrado. O Sistema ainda está vazio!
            </td>
          </tr>
          
          <tr v-for="tenant in tenants" :key="tenant.id" class="border-b border-gray-800 hover:bg-gray-800/50 transition-colors">
            <td class="py-4 px-6 font-medium text-white flex items-center space-x-3">
                <div class="w-8 h-8 rounded bg-gradient-to-tr from-gray-700 to-gray-600 flex items-center justify-center text-xs">
                    {{ tenant.name.charAt(0) }}
                </div>
                <span>{{ tenant.name }}</span>
            </td>
            <td class="py-4 px-6 text-gray-400">
               <span class="bg-green-500/10 text-green-500 px-2 py-1 rounded text-xs">Ativo</span>
            </td>
            <td class="py-4 px-6 text-gray-400">
                12 Fev, 2026
            </td>
            <td class="py-4 px-6 text-right">
                <button @click="impersonate(tenant.id)" title="Acessar como Inquilino" class="text-gray-400 hover:text-white p-2 rounded-lg hover:bg-gray-700 transition">
                    <LogIn class="w-5 h-5" />
                </button>
                <button title="Suspender" class="text-gray-400 hover:text-red-500 p-2 rounded-lg hover:bg-gray-700 transition ml-2">
                    <ShieldAlert class="w-5 h-5" />
                </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
