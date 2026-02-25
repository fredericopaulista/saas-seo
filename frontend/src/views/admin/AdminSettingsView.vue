<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import { Settings, Save, KeyRound } from 'lucide-vue-next'

const loading = ref(false)
const saving = ref(false)

const settings = ref({
    GOOGLE_CLIENT_ID: '',
    GOOGLE_CLIENT_SECRET: '',
    GOOGLE_REDIRECT_URI: '',
    OPENAI_API_KEY: ''
})

onMounted(async () => {
    fetchSettings()
})

const fetchSettings = async () => {
    loading.value = true
    try {
        const { data } = await api.get('/admin/settings')
        
        if (data.google_oauth) {
            data.google_oauth.forEach((item: any) => {
                if (item.key === 'GOOGLE_CLIENT_ID') settings.value.GOOGLE_CLIENT_ID = item.value || ''
                if (item.key === 'GOOGLE_CLIENT_SECRET') settings.value.GOOGLE_CLIENT_SECRET = item.value || ''
                if (item.key === 'GOOGLE_REDIRECT_URI') settings.value.GOOGLE_REDIRECT_URI = item.value || ''
            })
        }
        if (data.ai_services) {
            data.ai_services.forEach((item: any) => {
                if (item.key === 'OPENAI_API_KEY') settings.value.OPENAI_API_KEY = item.value || ''
            })
        }
    } catch (e) {
        console.error('Error fetching settings', e)
    } finally {
        loading.value = false
    }
}

const saveSettings = async () => {
    saving.value = true
    try {
        const payload = {
            settings: [
                {
                    key: 'GOOGLE_CLIENT_ID',
                    value: settings.value.GOOGLE_CLIENT_ID,
                    group: 'google_oauth'
                },
                {
                    key: 'GOOGLE_CLIENT_SECRET',
                    value: settings.value.GOOGLE_CLIENT_SECRET,
                    group: 'google_oauth'
                },
                {
                    key: 'GOOGLE_REDIRECT_URI',
                    value: settings.value.GOOGLE_REDIRECT_URI,
                    group: 'google_oauth'
                },
                {
                    key: 'OPENAI_API_KEY',
                    value: settings.value.OPENAI_API_KEY,
                    group: 'ai_services'
                }
            ]
        }
        
        await api.post('/admin/settings', payload)
        alert('Configurações Globais salvas com sucesso!')
    } catch (e) {
        console.error('Error saving', e)
        alert('Falha ao salvar as configurações.')
    } finally {
        saving.value = false
    }
}

</script>

<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center border-b border-gray-800 pb-5">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-3">
          <Settings class="w-6 h-6 text-gray-400" />
          Configurações Globais
        </h1>
        <p class="text-sm text-gray-400 mt-1">Gerencie chaves de API globais injetadas em toda a aplicação.</p>
      </div>
      <button @click="saveSettings" :disabled="saving" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2 transition-colors disabled:opacity-50">
          <Save class="w-4 h-4" />
          {{ saving ? 'Salvando...' : 'Salvar Alterações' }}
      </button>
    </div>

    <div v-if="loading" class="animate-pulse flex flex-col space-y-4">
        <div class="h-32 bg-gray-900 rounded-xl w-full"></div>
    </div>

    <div v-else class="grid grid-cols-1 gap-6">
        
        <!-- Google OAuth Card -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
            <div class="p-6 border-b border-gray-800 bg-gray-900/50 flex items-center gap-3">
                <div class="bg-blue-500/10 p-2 rounded-lg">
                    <KeyRound class="w-5 h-5 text-blue-500" />
                </div>
                <div>
                   <h3 class="font-bold text-white">Integração Google Oauth</h3>
                   <p class="text-xs text-gray-500 mt-1">Credenciais do Cloud Console para leitura do Search Console API</p>
                </div>
            </div>
            <div class="p-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Google Client ID</label>
                    <input 
                        v-model="settings.GOOGLE_CLIENT_ID" 
                        type="text" 
                        placeholder="Ex: 53213-abc123as.apps.googleusercontent.com" 
                        class="w-full bg-gray-950 border border-gray-800 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-indigo-500 font-mono text-sm"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Google Client Secret</label>
                    <input 
                        v-model="settings.GOOGLE_CLIENT_SECRET" 
                        type="password" 
                        placeholder="Ex: GOCSPX-12345678abcdefg" 
                        class="w-full bg-gray-950 border border-gray-800 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-indigo-500 font-mono text-sm"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Google Redirect URI</label>
                    <input 
                        v-model="settings.GOOGLE_REDIRECT_URI" 
                        type="text" 
                        placeholder="Ex: https://advogados.emp.br/api/auth/google/callback" 
                        class="w-full bg-gray-950 border border-gray-800 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-indigo-500 font-mono text-sm"
                    >
                    <p class="text-xs text-gray-500 mt-1">Geralmente é <code>https://SEU_DOMINIO/api/auth/google/callback</code></p>
                </div>
                
                <div class="bg-blue-500/10 border border-blue-500/20 text-blue-400 p-4 rounded-lg text-sm flex gap-3 items-start">
                    <p>
                        <strong>Importante:</strong> Após alterar essas chaves as configurações de OAuth serão sobrescritas no carregamento e a API do Google usará os novos tokens imediatamente. As segregrações de Oauth por tenant ainda seguem esse app global.
                    </p>
                </div>
            </div>
        </div>

        <!-- AI Engine Card -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden mt-6">
            <div class="p-6 border-b border-gray-800 bg-gray-900/50 flex items-center gap-3">
                <div class="bg-purple-500/10 p-2 rounded-lg">
                    <KeyRound class="w-5 h-5 text-purple-500" />
                </div>
                <div>
                   <h3 class="font-bold text-white">Motor de Inteligência Artificial</h3>
                   <p class="text-xs text-gray-500 mt-1">Credenciais do OpenAI / LLMs para geração de Auditorias Autônomas (Insights)</p>
                </div>
            </div>
            <div class="p-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">OpenAI API Key (ChatGPT)</label>
                    <input 
                        v-model="settings.OPENAI_API_KEY" 
                        type="password" 
                        placeholder="Ex: sk-proj-123456789abc..." 
                        class="w-full bg-gray-950 border border-gray-800 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-indigo-500 font-mono text-sm"
                    >
                </div>
                
                 <div class="bg-purple-500/10 border border-purple-500/20 text-purple-400 p-4 rounded-lg text-sm flex gap-3 items-start">
                    <p>
                        <strong>Dica:</strong> Essa chave é utilizada no Job diário que analisa os dados do Search Console puxados dos clientes e escreve sugestões de melhoria (Opportunities e Anomalies) na página de Insights de cada Projeto.
                    </p>
                </div>
            </div>
        </div>

    </div>
  </div>
</template>
