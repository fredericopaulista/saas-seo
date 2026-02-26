<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import { Settings, Save, KeyRound } from 'lucide-vue-next'

const loading = ref(false)
const saving = ref(false)
const registeringWebhook = ref(false)

const settings = ref({
    GOOGLE_CLIENT_ID: '',
    GOOGLE_CLIENT_SECRET: '',
    GOOGLE_REDIRECT_URI: '',
    OPENAI_API_KEY: '',
    ASAAS_ENVIRONMENT: 'sandbox',
    ASAAS_API_KEY: '',
    ASAAS_WEBHOOK_TOKEN: '',
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
        if (data.asaas_gateway) {
            data.asaas_gateway.forEach((item: any) => {
                if (item.key === 'ASAAS_ENVIRONMENT') settings.value.ASAAS_ENVIRONMENT = item.value || 'sandbox'
                if (item.key === 'ASAAS_API_KEY') settings.value.ASAAS_API_KEY = item.value || ''
                if (item.key === 'ASAAS_WEBHOOK_TOKEN') settings.value.ASAAS_WEBHOOK_TOKEN = item.value || ''
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
                },
                {
                    key: 'ASAAS_ENVIRONMENT',
                    value: settings.value.ASAAS_ENVIRONMENT,
                    group: 'asaas_gateway'
                },
                {
                    key: 'ASAAS_API_KEY',
                    value: settings.value.ASAAS_API_KEY,
                    group: 'asaas_gateway'
                },
                {
                    key: 'ASAAS_WEBHOOK_TOKEN',
                    value: settings.value.ASAAS_WEBHOOK_TOKEN,
                    group: 'asaas_gateway'
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

const registerWebhook = async () => {
    if (!settings.value.ASAAS_WEBHOOK_TOKEN) {
        alert('Configure e salve o Webhook Token antes de registrar.')
        return
    }
    registeringWebhook.value = true
    try {
        const { data } = await api.post('/admin/billing/register-webhook')
        alert(`Webhook registrado com sucesso!\nURL: ${data.webhook_url}`)
    } catch (e: any) {
        alert(e.response?.data?.error || 'Falha ao registrar webhook no Asaas.')
    } finally {
        registeringWebhook.value = false
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

        <!-- Asaas Gateway Card -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden mt-6">
            <div class="p-6 border-b border-gray-800 bg-gray-900/50 flex items-center gap-3">
                <div class="bg-emerald-500/10 p-2 rounded-lg">
                    <KeyRound class="w-5 h-5 text-emerald-500" />
                </div>
                <div>
                   <h3 class="font-bold text-white">Gateway de Pagamento (Asaas)</h3>
                   <p class="text-xs text-gray-500 mt-1">Configurações para faturamento, cobranças e assinaturas via Asaas API.</p>
                </div>
            </div>
            <div class="p-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Ambiente de Operação</label>
                    <select 
                        v-model="settings.ASAAS_ENVIRONMENT" 
                        class="w-full bg-gray-950 border border-gray-800 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-indigo-500 font-mono text-sm"
                    >
                        <option value="sandbox">Sandbox (Testes)</option>
                        <option value="production">Produção (Real)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Asaas API Key (access_token)</label>
                    <input 
                        v-model="settings.ASAAS_API_KEY" 
                        type="password" 
                        placeholder="Ex: $aact_YTU5YTE0M2M2N2I4MTliNDgw..." 
                        class="w-full bg-gray-950 border border-gray-800 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-indigo-500 font-mono text-sm"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Webhook Token (asaas-access-token)</label>
                    <input 
                        v-model="settings.ASAAS_WEBHOOK_TOKEN" 
                        type="password" 
                        placeholder="Ex: a3f8b2c1d4e5f6a7b8c9d0e1f2a3b4c5..." 
                        class="w-full bg-gray-950 border border-gray-800 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-indigo-500 font-mono text-sm"
                    >
                    <p class="text-xs text-gray-500 mt-1">Gere um token seguro: <code class="bg-gray-800 px-1 rounded">openssl rand -hex 32</code></p>
                </div>

                <div class="flex items-center gap-3 pt-1">
                    <button
                        @click="registerWebhook"
                        :disabled="registeringWebhook || !settings.ASAAS_WEBHOOK_TOKEN"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 disabled:bg-gray-700 disabled:text-gray-500 disabled:cursor-not-allowed text-white text-sm font-bold rounded-lg transition-all flex items-center gap-2"
                    >
                        <div v-if="registeringWebhook" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                        <span v-else>🔗</span>
                        {{ registeringWebhook ? 'Registrando...' : 'Registrar Webhook no Asaas' }}
                    </button>
                    <p class="text-xs text-gray-500">Salve as configurações antes de registrar.</p>
                </div>
                 
                 <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-4 rounded-lg text-sm flex gap-3 items-start">
                    <p>
                        <strong>Segurança:</strong> A chave de API e o Webhook Token são criptografados (AES-256-CBC) antes de serem salvos no banco de dados. Nenhuma credencial é armazenada em texto plano.
                    </p>
                </div>
            </div>
        </div>

    </div>
  </div>
</template>
