<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import { FolderGit2, Plus, Globe, Settings, Trash2 } from 'lucide-vue-next'

const projects = ref<any[]>([])
const loading = ref(true)

// Modal State
const isModalOpen = ref(false)
const newProject = ref({
    name: '',
    domain: '',
    country: 'BR',
    language: 'pt',
    gsc_property: ''
})
const isSubmitting = ref(false)

onMounted(async () => {
    await fetchProjects()
})

const fetchProjects = async () => {
    loading.value = true
    try {
        const { data } = await api.get('/projects')
        projects.value = data
    } catch (e) {
        console.error('Failed fetching projects')
    } finally {
        loading.value = false
    }
}

const submitProject = async () => {
    isSubmitting.value = true
    try {
        await api.post('/projects', newProject.value)
        isModalOpen.value = false
        newProject.value = { name: '', domain: '', country: 'BR', language: 'pt', gsc_property: '' }
        await fetchProjects()
    } catch (e: any) {
        alert(e.response?.data?.message || 'Erro ao criar projeto.')
    } finally {
        isSubmitting.value = false
    }
}

const deleteProject = async (id: number) => {
    if (confirm('Tem certeza que deseja deletar este projeto? Todos os dados históricos serão perdidos.')) {
        try {
            await api.delete(`/projects/${id}`)
            await fetchProjects()
        } catch (e: any) {
             alert('Erro ao deletar projeto.')
        }
    }
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center border-b border-gray-800 pb-5">
      <div>
        <h1 class="text-3xl font-bold tracking-tight text-white flex items-center gap-3">
          <FolderGit2 class="w-8 h-8 text-indigo-500" />
          Projetos e Websites
        </h1>
        <p class="text-gray-400 mt-2">Gerencie as propriedades do Google Search Console monitoradas pelo SaaS.</p>
      </div>
      <button @click="isModalOpen = true" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2 transition-colors">
          <Plus class="w-4 h-4" />
          Novo Projeto
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 animate-pulse">
        <div v-for="i in 3" :key="i" class="h-40 bg-gray-900 rounded-xl border border-gray-800"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="projects.length === 0" class="text-center py-20 bg-gray-900 border border-gray-800 rounded-xl">
        <Globe class="w-16 h-16 text-gray-700 mx-auto mb-4" />
        <h3 class="text-xl font-bold text-white mb-2">Nenhum projeto monitorado</h3>
        <p class="text-gray-400 max-w-md mx-auto mb-6">Comece conectando sua primeira propriedade do Google Search Console para extração de dados e análises de SEO.</p>
        <button @click="isModalOpen = true" class="bg-indigo-500 hover:bg-indigo-600 text-white px-6 py-2 rounded-lg font-medium transition-colors">
            Adicionar Site
        </button>
    </div>

    <!-- Projects Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="project in projects" :key="project.id" class="bg-gray-900 border border-gray-800 rounded-xl p-6 hover:border-gray-700 transition relative group">
            <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition flex gap-2">
                <button title="Configurações" class="p-2 text-gray-400 hover:text-white bg-gray-800 rounded-lg hover:bg-gray-700">
                    <Settings class="w-4 h-4" />
                </button>
                <button @click="deleteProject(project.id)" title="Deletar Projeto" class="p-2 text-gray-400 hover:text-red-500 bg-gray-800 rounded-lg hover:bg-gray-700">
                    <Trash2 class="w-4 h-4" />
                </button>
            </div>
            
            <div class="flex items-center space-x-3 mb-4">
                <div class="bg-indigo-500/10 p-3 rounded-lg text-indigo-500">
                    <Globe class="w-6 h-6" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white truncate max-w-[200px]">{{ project.name }}</h3>
                    <a :href="project.domain" target="_blank" class="text-xs text-indigo-400 hover:underline truncate">{{ project.domain }}</a>
                </div>
            </div>

            <div class="space-y-2 mt-4 pt-4 border-t border-gray-800">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">GSC Property</span>
                    <span class="text-gray-300 font-mono text-xs">{{ project.gsc_property }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Segmentação</span>
                    <span class="text-gray-300 uppercase">{{ project.language }}-{{ project.country }}</span>
                </div>
            </div>
            
            <div class="mt-6 flex gap-3">
                <router-link :to="`/dashboard/projects/${project.id}`" class="w-full text-center bg-gray-800 hover:bg-gray-700 text-white py-2 rounded-lg text-sm font-medium transition">
                    Ver Dashboard
                </router-link>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div v-if="isModalOpen" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50">
        <div class="bg-gray-900 rounded-xl max-w-md w-full border border-gray-800 p-6">
            <h2 class="text-xl font-bold text-white mb-4">Novo Projeto</h2>
            <form @submit.prevent="submitProject" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Nome do Projeto</label>
                    <input v-model="newProject.name" required type="text" placeholder="Ex: E-commerce Brasil" class="w-full bg-gray-950 border border-gray-800 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">URL / Domínio</label>
                    <input v-model="newProject.domain" required type="url" placeholder="https://meusite.com.br" class="w-full bg-gray-950 border border-gray-800 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-indigo-500">
                </div>
                 <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Propriedade Exata no GSC</label>
                    <input v-model="newProject.gsc_property" required type="text" placeholder="sc-domain:meusite.com.br ou https://..." class="w-full bg-gray-950 border border-gray-800 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-indigo-500">
                    <p class="text-xs text-gray-500 mt-1">Copie exatamente como está no Google Search Console.</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">País</label>
                        <select v-model="newProject.country" class="w-full bg-gray-950 border border-gray-800 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-indigo-500">
                            <option value="br">Brasil (BR)</option>
                            <option value="us">US</option>
                            <option value="pt">Portugal</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">Idioma</label>
                        <select v-model="newProject.language" class="w-full bg-gray-950 border border-gray-800 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-indigo-500">
                            <option value="pt">Português</option>
                            <option value="en">Inglês</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="isModalOpen = false" class="text-gray-400 hover:text-white px-4 py-2 text-sm font-medium">Cancelar</button>
                    <button type="submit" :disabled="isSubmitting" class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white px-5 py-2 rounded-lg text-sm font-medium transition-colors">
                        {{ isSubmitting ? 'Salvando...' : 'Criar Projeto' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
  </div>
</template>
