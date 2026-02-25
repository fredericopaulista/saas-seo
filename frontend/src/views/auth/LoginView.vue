<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

const form = ref({
  email: '',
  password: ''
})
const error = ref('')
const loading = ref(false)

const handleLogin = async () => {
  try {
    loading.value = true
    error.value = ''
    await authStore.login(form.value)
    router.push('/dashboard')
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Login falhou. Verifique suas credenciais.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-xl shadow-lg border border-gray-100">
      <div>
        <h2 class="mt-2 text-center text-3xl font-extrabold text-gray-900">
          SaaS SEO
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Faça login para acessar o painel
        </p>
      </div>
      <form class="mt-8 space-y-6" @submit.prevent="handleLogin">
        
        <div v-if="error" class="bg-red-50 text-red-500 p-3 rounded-md text-sm text-center">
          {{ error }}
        </div>

        <div class="rounded-md shadow-sm space-y-4">
          <div>
            <label for="email-address" class="sr-only">E-mail</label>
            <input 
              id="email-address" 
              name="email" 
              type="email" 
              autocomplete="email" 
              required 
              v-model="form.email"
              class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary-500 focus:border-primary-500 focus:z-10 sm:text-sm" 
              placeholder="E-mail" 
            />
          </div>
          <div>
            <label for="password" class="sr-only">Senha</label>
            <input 
              id="password" 
              name="password" 
              type="password" 
              autocomplete="current-password" 
              required 
              v-model="form.password"
              class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary-500 focus:border-primary-500 focus:z-10 sm:text-sm" 
              placeholder="Senha" 
            />
          </div>
        </div>

        <div>
          <button 
            type="submit" 
            :disabled="loading"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-75 disabled:cursor-not-allowed"
          >
            <span v-if="loading">Entrando...</span>
            <span v-else>Entrar</span>
          </button>
        </div>
        
        <div class="text-sm text-center">
          <p class="text-gray-500 mt-4">
            Ou acesse usando o Google:
          </p>
          <a href="http://localhost:8000/api/auth/google" class="mt-2 text-primary-600 hover:text-primary-500 font-medium inline-block">
            Login via Google Search Console
          </a>
        </div>
      </form>
    </div>
  </div>
</template>
