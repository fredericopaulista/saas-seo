<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
})
const error = ref('')
const loading = ref(false)

const handleRegister = async () => {
  if (form.value.password !== form.value.password_confirmation) {
    error.value = 'As senhas não coincidem.'
    return
  }

  try {
    loading.value = true
    error.value = ''
    await authStore.register(form.value)
    router.push('/dashboard')
  } catch (err: any) {
    error.value = err.response?.data?.message || err.response?.data?.errors?.email?.[0] || 'Falha no registro. Verifique os dados.'
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
          Crie sua conta para automatizar seu SEO
        </p>
      </div>
      <form class="mt-8 space-y-6" @submit.prevent="handleRegister">
        
        <div v-if="error" class="bg-red-50 text-red-500 p-3 rounded-md text-sm text-center">
          {{ error }}
        </div>

        <div class="rounded-md shadow-sm space-y-4">
          <div>
            <label for="name" class="sr-only">Nome</label>
            <input 
              id="name" 
              name="name" 
              type="text" 
              required 
              v-model="form.name"
              class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary-500 focus:border-primary-500 focus:z-10 sm:text-sm" 
              placeholder="Nome Completo" 
            />
          </div>
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
              autocomplete="new-password" 
              required 
              v-model="form.password"
              class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary-500 focus:border-primary-500 focus:z-10 sm:text-sm" 
              placeholder="Senha" 
            />
          </div>
          <div>
            <label for="password_confirmation" class="sr-only">Confirme a Senha</label>
            <input 
              id="password_confirmation" 
              name="password_confirmation" 
              type="password" 
              autocomplete="new-password" 
              required 
              v-model="form.password_confirmation"
              class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary-500 focus:border-primary-500 focus:z-10 sm:text-sm" 
              placeholder="Confirme a Senha" 
            />
          </div>
        </div>

        <div>
          <button 
            type="submit" 
            :disabled="loading"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-75 disabled:cursor-not-allowed"
          >
            <span v-if="loading">Registrando...</span>
            <span v-else>Criar Conta</span>
          </button>
        </div>
        
        <div class="text-sm text-center">
          <p class="text-gray-500 mt-4">
            Já tem uma conta? <router-link to="/login" class="text-primary-600 hover:text-primary-500 font-medium inline-block">Faça login</router-link>
          </p>
        </div>
      </form>
    </div>
  </div>
</template>
