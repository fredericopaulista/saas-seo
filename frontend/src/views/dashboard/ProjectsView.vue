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
    <div class="flex justify-between items-center border-b border-white/5 pb-6">
      <div>
        <h1 class="text-3xl font-black tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-white to-gray-400 flex items-center gap-3">
          <FolderGit2 class="w-8 h-8 text-indigo-400" />
          Projetos e Websites
        </h1>
        <p class="text-gray-400 mt-2 font-medium">Gerencie as propriedades do Google Search Console monitoradas pelo SaaS.</p>
      </div>
      <button @click="isModalOpen = true" class="relative group overflow-hidden bg-white/5 hover:bg-white/10 text-white px-5 py-2.5 rounded-xl font-bold flex items-center gap-2 border border-white/10 transition-all duration-300 shadow-lg">
          <div class="absolute inset-0 bg-gradient-to-r from-indigo-500/20 to-purple-500/20 opacity-0 group-hover:opacity-100 transition-opacity"></div>
          <Plus class="w-4 h-4 relative z-10" />
          <span class="relative z-10">Novo Projeto</span>
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 animate-pulse">
        <div v-for="i in 3" :key="i" class="h-64 bg-white/5 rounded-3xl border border-white/5 backdrop-blur-md"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="projects.length === 0" class="text-center py-24 bg-[#0A0A0A]/40 border border-white/5 rounded-3xl backdrop-blur-md relative overflow-hidden">
        <div class="absolute -right-20 -top-20 w-96 h-96 bg-indigo-500/5 rounded-full blur-3xl"></div>
        <Globe class="w-20 h-20 text-gray-700 mx-auto mb-6 relative z-10" />
        <h3 class="text-2xl font-black text-white mb-3 relative z-10">Nenhum projeto monitorado</h3>
        <p class="text-gray-400 max-w-md mx-auto mb-8 font-medium relative z-10">Comece conectando sua primeira propriedade do Google Search Console para extração de dados e análises de SEO.</p>
        <button @click="isModalOpen = true" class="relative group overflow-hidden bg-white/5 hover:bg-white/10 text-white px-8 py-3 rounded-xl font-bold border border-white/10 transition-all duration-300 shadow-xl z-10">
            <div class="absolute inset-0 bg-gradient-to-r from-indigo-500/20 to-purple-500/20 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <span class="relative z-10">Adicionar Site</span>
        </button>
    </div>

    <!-- Projects Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div v-for="project in projects" :key="project.id" class="bg-[#0A0A0A]/60 border border-white/5 rounded-3xl p-8 hover:border-white/10 shadow-2xl backdrop-blur-md transition-all duration-500 relative group overflow-hidden flex flex-col justify-between">
            <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-indigo-500/10 rounded-full blur-3xl group-hover:bg-purple-500/20 transition duration-700 pointer-events-none"></div>
            
            <div class="absolute top-6 right-6 opacity-0 group-hover:opacity-100 transition duration-300 flex gap-2 z-20">
                <button title="Configurações" class="p-2 text-gray-400 hover:text-white bg-white/5 border border-white/5 rounded-xl hover:bg-white/10 backdrop-blur-md transition-colors">
                    <Settings class="w-4 h-4" />
                </button>
                <button @click="deleteProject(project.id)" title="Deletar Projeto" class="p-2 text-gray-400 hover:text-red-400 bg-white/5 border border-white/5 rounded-xl hover:bg-red-500/10 backdrop-blur-md transition-colors">
                    <Trash2 class="w-4 h-4" />
                </button>
            </div>
            
            <div class="flex items-start space-x-4 mb-6 relative z-10 w-10/12">
                <div class="bg-gradient-to-br from-indigo-500/20 to-purple-500/20 p-4 rounded-2xl text-indigo-400 border border-white/5 shadow-inner">
                    <Globe class="w-7 h-7" />
                </div>
                <div class="pt-1">
                    <h3 class="text-xl font-bold text-white truncate max-w-[200px] tracking-tight">{{ project.name }}</h3>
                    <a :href="project.domain" target="_blank" class="text-sm text-gray-500 hover:text-indigo-400 transition-colors truncate block mt-0.5">{{ project.domain }}</a>
                </div>
            </div>

            <div class="space-y-3 mt-2 pt-6 border-t border-white/5 relative z-10">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">GSC Property</span>
                    <span class="text-gray-300 font-mono text-xs truncate max-w-[150px] bg-white/5 px-2 py-1 rounded-md">{{ project.gsc_property }}</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Tracking</span>
                    <span class="text-gray-300 uppercase font-medium bg-white/5 px-2 py-1 rounded-md">{{ project.language }}-{{ project.country }}</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Google Token</span>
                    <span v-if="project.search_console_token" class="text-emerald-400 font-bold text-[11px] uppercase tracking-wider flex items-center bg-emerald-400/10 px-2 py-1 rounded-md border border-emerald-400/20">
                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-400 mr-1.5"></div> Ativo
                    </span>
                    <span v-else class="text-amber-400 font-bold text-[11px] uppercase tracking-wider flex items-center bg-amber-400/10 px-2 py-1 rounded-md border border-amber-400/20">
                         <div class="w-1.5 h-1.5 rounded-full bg-amber-400 mr-1.5 animate-pulse"></div> Pendente
                    </span>
                </div>
            </div>
            
            <div class="mt-8 flex flex-col gap-3 relative z-10">
                <a 
                    v-if="!project.search_console_token"
                    :href="`http://localhost:8000/api/auth/google?project_id=${project.id}`" 
                    target="_blank"
                    class="w-full text-center bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 py-3 rounded-xl text-sm font-bold transition duration-300 border border-indigo-500/20 shadow-inner"
                >
                    Conectar Conta Google
                </a>
                
                <router-link :to="`/dashboard/projects/${project.id}`" class="w-full text-center bg-white/5 hover:bg-white/10 text-white py-3 rounded-xl text-sm font-bold transition duration-300 border border-white/5 shadow-inner">
                    Ver Informações
                </router-link>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div v-if="isModalOpen" class="fixed inset-0 flex items-center justify-center p-4 z-50">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="isModalOpen = false"></div>
        
        <div class="relative bg-[#0A0A0A] rounded-[2rem] max-w-lg w-full border border-white/10 p-8 shadow-2xl overflow-hidden ring-1 ring-white/5">
            <div class="absolute -right-20 -top-20 w-60 h-60 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
            
            <h2 class="text-2xl font-black text-white mb-6 relative z-10 tracking-tight">Novo Projeto</h2>
            <form @submit.prevent="submitProject" class="space-y-5 relative z-10">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Nome de Identificação</label>
                    <input v-model="newProject.name" required type="text" placeholder="Ex: E-commerce Brasil" class="w-full bg-[#111] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all font-medium placeholder-gray-600">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Domínio Hospedado</label>
                    <input v-model="newProject.domain" required type="url" placeholder="https://meusite.com.br" class="w-full bg-[#111] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all font-medium placeholder-gray-600">
                </div>
                 <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Propriedade no GSC</label>
                    <input v-model="newProject.gsc_property" required type="text" placeholder="sc-domain:meusite.com.br" class="w-full bg-[#111] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all font-mono text-sm placeholder-gray-600">
                    <p class="text-xs text-gray-500 mt-2 font-medium">Copie exatamente como preenchido na sua conta Google Search Console.</p>
                </div>
                <div class="grid grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">País (Target)</label>
                        <select v-model="newProject.country" class="w-full bg-[#111] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all font-medium appearance-none">
                            <option value="br">Brasil (BR)</option>
                            <option value="us">United States (US)</option>
                            <option value="pt">Portugal (PT)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Idioma</label>
                        <select v-model="newProject.language" class="w-full bg-[#111] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all font-medium appearance-none">
                            <option value="pt">Português (pt)</option>
                            <option value="en">Inglês (en)</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-white/5">
                    <button type="button" @click="isModalOpen = false" class="text-gray-400 hover:text-white px-5 py-2.5 text-sm font-bold transition-colors">Cancelar</button>
                    <button type="submit" :disabled="isSubmitting" class="bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white px-6 py-2.5 rounded-xl text-sm font-bold transition-all shadow-[0_0_15px_rgba(79,70,229,0.5)]">
                        {{ isSubmitting ? 'Iniciando Setup...' : 'Confirmar e Criar' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
  </div>
</template>
