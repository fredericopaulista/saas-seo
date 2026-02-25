<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import { Server, Edit2, Trash2, PlusCircle } from 'lucide-vue-next'

const plans = ref<any[]>([])
const loading = ref(true)

const showModal = ref(false)
const isEditing = ref(false)
const currentPlan = ref<any>({})

onMounted(async () => {
    await loadPlans()
})

const loadPlans = async () => {
    loading.value = true
    try {
        const { data } = await api.get('/admin/plans')
        plans.value = data
    } catch (e) {
        console.error('Failed to load plans:', e)
    } finally {
        loading.value = false
    }
}

const openCreateModal = () => {
    isEditing.value = false
    currentPlan.value = {
        name: '',
        slug: '',
        price: 0,
        max_projects: 1,
        billing_cycle: 'MONTHLY',
        features_json: []
    }
    showModal.value = true
}

const openEditModal = (plan: any) => {
    isEditing.value = true
    currentPlan.value = { ...plan }
    showModal.value = true
}

const savePlan = async () => {
    try {
        if (isEditing.value) {
            await api.put(`/admin/plans/${currentPlan.value.id}`, currentPlan.value)
        } else {
            // Provide a dummy feature array if empty for simplicity
            if (!currentPlan.value.features_json || currentPlan.value.features_json.length === 0) {
                currentPlan.value.features_json = ['Standard Feature']
            }
            await api.post('/admin/plans', currentPlan.value)
        }
        showModal.value = false
        await loadPlans()
    } catch (e: any) {
        alert(e.response?.data?.message || 'Falha ao salvar plano.')
    }
}

const deletePlan = async (id: number) => {
    if (confirm('Tem certeza que deseja apagar este plano?')) {
        try {
            await api.delete(`/admin/plans/${id}`)
            await loadPlans()
        } catch (e: any) {
            alert(e.response?.data?.message || 'Falha ao apagar plano.')
        }
    }
}

</script>

<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold tracking-tight text-white flex items-center gap-3">
          <Server class="w-8 h-8 text-indigo-500" />
          Planos (Gateway)
        </h1>
        <p class="text-gray-400 mt-2">Crie e edite os planos comercializados na plataforma de SEO.</p>
      </div>
      <button @click="openCreateModal" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2">
          <PlusCircle class="w-5 h-5"/>
          Novo Plano
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center p-20">
      <div class="w-8 h-8 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div v-for="plan in plans" :key="plan.id" class="bg-gray-900 border border-gray-800 rounded-xl p-6 relative">
          <div class="flex justify-between items-start mb-4">
            <h3 class="font-bold text-lg text-white">{{ plan.name }}</h3>
            <span class="px-2 py-1 bg-gray-800 rounded text-xs text-gray-400">R$ {{ plan.price }} / {{ plan.billing_cycle }}</span>
          </div>
          <div class="text-sm text-gray-500 mb-6">Slug: {{ plan.slug }}</div>
          
          <div class="flex gap-2">
              <button @click="openEditModal(plan)" class="flex-1 bg-gray-800 hover:bg-gray-700 text-white py-2 rounded flex justify-center items-center gap-2 text-sm transition-colors">
                  <Edit2 class="w-4 h-4"/> Editar
              </button>
              <button @click="deletePlan(plan.id)" class="flex-none bg-red-500/10 hover:bg-red-500/20 text-red-500 p-2 rounded flex justify-center items-center transition-colors">
                  <Trash2 class="w-4 h-4"/>
              </button>
          </div>
        </div>
    </div>

    <!-- Simple Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-gray-900 border border-gray-800 rounded-2xl w-full max-w-md p-6">
            <h2 class="text-xl font-bold text-white mb-6">{{ isEditing ? 'Editar Plano' : 'Criar Plano' }}</h2>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Nome do Plano</label>
                    <input v-model="currentPlan.name" type="text" class="w-full bg-gray-950 border border-gray-800 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-indigo-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Identificador (Slug)</label>
                    <input v-model="currentPlan.slug" type="text" class="w-full bg-gray-950 border border-gray-800 rounded-lg px-4 py-2 text-white outline-none focus:border-indigo-500" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">Preço (R$)</label>
                        <input v-model="currentPlan.price" type="number" step="0.01" class="w-full bg-gray-950 border border-gray-800 rounded-lg px-4 py-2 text-white outline-none focus:border-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">Max Projetos</label>
                        <input v-model="currentPlan.max_projects" type="number" class="w-full bg-gray-950 border border-gray-800 rounded-lg px-4 py-2 text-white outline-none focus:border-indigo-500" />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Ciclo de Cobrança</label>
                    <select v-model="currentPlan.billing_cycle" class="w-full bg-gray-950 border border-gray-800 rounded-lg px-4 py-2 text-white outline-none focus:border-indigo-500">
                        <option value="MONTHLY">Mensal</option>
                        <option value="BIMONTHLY">Bimestral</option>
                        <option value="QUARTERLY">Trimestral</option>
                        <option value="SEMIANNUALLY">Semestral</option>
                        <option value="YEARLY">Anual</option>
                    </select>
                </div>
            </div>

            <div class="mt-8 flex justify-end gap-3">
                <button @click="showModal = false" class="px-4 py-2 text-gray-400 hover:text-white transition-colors">Cancelar</button>
                <button @click="savePlan" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    Salvar
                </button>
            </div>
        </div>
    </div>

  </div>
</template>
