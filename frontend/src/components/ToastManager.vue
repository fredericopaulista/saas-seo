<script setup lang="ts">
import { useUIStore } from '@/stores/ui'
import { 
    CheckCircle2, 
    AlertCircle, 
    Info, 
    AlertTriangle, 
    X 
} from 'lucide-vue-next'

const uiStore = useUIStore()

const getIcon = (type: string) => {
    switch (type) {
        case 'success': return CheckCircle2
        case 'error': return AlertCircle
        case 'warning': return AlertTriangle
        default: return Info
    }
}

const getClasses = (type: string) => {
    switch (type) {
        case 'success': return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
        case 'error': return 'bg-red-500/10 text-red-400 border-red-500/20'
        case 'warning': return 'bg-amber-500/10 text-amber-400 border-amber-500/20'
        default: return 'bg-blue-500/10 text-blue-400 border-blue-500/20'
    }
}
</script>

<template>
    <div class="fixed top-6 right-6 z-[100] flex flex-col gap-3 pointer-events-none w-full max-w-sm">
        <TransitionGroup 
            name="toast" 
            tag="div" 
            class="flex flex-col gap-3"
        >
            <div 
                v-for="toast in uiStore.toasts" 
                :key="toast.id"
                class="pointer-events-auto flex items-start gap-3 p-4 rounded-xl border backdrop-blur-md shadow-lg transition-all duration-300 transform"
                :class="getClasses(toast.type)"
            >
                <component :is="getIcon(toast.type)" class="w-5 h-5 flex-shrink-0 mt-0.5" />
                <div class="flex-1 text-sm font-medium pr-2">
                    {{ toast.message }}
                </div>
                <button 
                    @click="uiStore.removeToast(toast.id)"
                    class="text-gray-400 hover:text-white transition-colors"
                >
                    <X class="w-4 h-4" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
.toast-enter-from {
    opacity: 0;
    transform: translateX(30px) scale(0.9);
}
.toast-enter-active, .toast-leave-active {
    transition: all 0.3s ease;
}
.toast-leave-to {
    opacity: 0;
    transform: translateX(30px);
}
</style>
