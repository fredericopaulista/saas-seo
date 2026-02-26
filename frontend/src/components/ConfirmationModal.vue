<script setup lang="ts">
import { useUIStore } from '@/stores/ui'
import { AlertTriangle, Info, HelpCircle } from 'lucide-vue-next'

const uiStore = useUIStore()

const getIcon = (type: string | undefined) => {
    if (type === 'danger') return AlertTriangle
    if (type === 'warning') return AlertTriangle
    return HelpCircle
}

const getBgClass = (type: string | undefined) => {
    if (type === 'danger') return 'bg-red-500/10 text-red-500'
    if (type === 'warning') return 'bg-amber-500/10 text-amber-500'
    return 'bg-blue-500/10 text-blue-500'
}

const getButtonClass = (type: string | undefined) => {
    if (type === 'danger') return 'bg-red-600 hover:bg-red-700'
    if (type === 'warning') return 'bg-amber-600 hover:bg-amber-700'
    return 'bg-indigo-600 hover:bg-indigo-700'
}
</script>

<template>
    <Transition name="fade">
        <div v-if="uiStore.showConfirm" class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="bg-gray-900 border border-gray-800 rounded-2xl w-full max-w-md shadow-2xl overflow-hidden transform transition-all">
                <div class="p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="p-3 rounded-full" :class="getBgClass(uiStore.confirmOptions.type)">
                            <component :is="getIcon(uiStore.confirmOptions.type)" class="w-6 h-6" />
                        </div>
                        <h3 class="text-xl font-bold text-white">{{ uiStore.confirmOptions.title }}</h3>
                    </div>
                    
                    <p class="text-gray-300 leading-relaxed mb-6">
                        {{ uiStore.confirmOptions.message }}
                    </p>

                    <div class="flex gap-3 justify-end">
                        <button 
                            @click="uiStore.handleConfirm(false)"
                            class="px-5 py-2 border border-gray-700 text-gray-300 hover:bg-gray-800 rounded-xl font-medium transition-all"
                        >
                            {{ uiStore.confirmOptions.cancelText }}
                        </button>
                        <button 
                            @click="uiStore.handleConfirm(true)"
                            class="px-5 py-2 text-white rounded-xl font-medium transition-all"
                            :class="getButtonClass(uiStore.confirmOptions.type)"
                        >
                            {{ uiStore.confirmOptions.confirmText }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.fade-enter-active .bg-gray-900 {
  animation: modal-in 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes modal-in {
  from { transform: scale(0.9); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}
</style>
