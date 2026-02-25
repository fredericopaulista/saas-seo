import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../services/api'
import { useRouter } from 'vue-router'

export const useAuthStore = defineStore('auth', () => {
    const user = ref<any>(null)
    const isAdmin = ref<boolean>(false)
    const token = ref<string | null>(localStorage.getItem('auth_token'))
    const router = useRouter()

    const login = async (credentials: any) => {
        // CSRF initialization for Sanctum
        await api.get('http://localhost:8000/sanctum/csrf-cookie')

        const response = await api.post('/auth/login', credentials)
        token.value = response.data.access_token
        user.value = response.data.user

        localStorage.setItem('auth_token', token.value!)
    }

    const logout = async () => {
        try {
            await api.post('/auth/logout')
        } catch (error) {
            console.error('Logout error skipped on frontend.', error)
        } finally {
            token.value = null
            user.value = null
            isAdmin.value = false
            localStorage.removeItem('auth_token')
            router.push('/login')
        }
    }

    const fetchUser = async () => {
        if (!token.value) return

        try {
            const response = await api.get('/auth/me')
            user.value = response.data
            isAdmin.value = response.data?.role_id !== undefined // Heuristic indication of AdminUser
        } catch (e) {
            // If token is invalid/expired
            token.value = null
            user.value = null
            isAdmin.value = false
            localStorage.removeItem('auth_token')
        }
    }

    const isAuthenticated = () => !!token.value

    return { user, isAdmin, token, login, logout, fetchUser, isAuthenticated }
})
