import { defineStore } from 'pinia'
import { ref } from 'vue'

export type ToastType = 'success' | 'error' | 'warning' | 'info'

interface Toast {
    id: number
    message: string
    type: ToastType
    duration?: number
}

interface ConfirmationOptions {
    title: string
    message: string
    confirmText?: string
    cancelText?: string
    type?: 'danger' | 'info' | 'warning'
}

export const useUIStore = defineStore('ui', () => {
    const toasts = ref<Toast[]>([])
    const counter = ref(0)

    // Confirmation Modal State
    const showConfirm = ref(false)
    const confirmOptions = ref<ConfirmationOptions>({
        title: 'Confirmação',
        message: '',
        confirmText: 'Confirmar',
        cancelText: 'Cancelar',
        type: 'info'
    })

    let resolveConfirm: ((val: boolean) => void) | null = null

    const addToast = (message: string, type: ToastType = 'info', duration = 4000) => {
        const id = counter.value++
        toasts.value.push({ id, message, type, duration })

        setTimeout(() => {
            removeToast(id)
        }, duration)
    }

    const removeToast = (id: number) => {
        const index = toasts.value.findIndex(t => t.id === id)
        if (index > -1) {
            toasts.value.splice(index, 1)
        }
    }

    const confirm = (options: ConfirmationOptions): Promise<boolean> => {
        confirmOptions.value = {
            title: options.title || 'Confirmação',
            message: options.message,
            confirmText: options.confirmText || 'Confirmar',
            cancelText: options.cancelText || 'Cancelar',
            type: options.type || 'info'
        }
        showConfirm.value = true

        return new Promise((resolve) => {
            resolveConfirm = resolve
        })
    }

    const handleConfirm = (result: boolean) => {
        showConfirm.value = false
        if (resolveConfirm) {
            resolveConfirm(result)
            resolveConfirm = null
        }
    }

    return {
        toasts,
        addToast,
        removeToast,
        showConfirm,
        confirmOptions,
        confirm,
        handleConfirm
    }
})
