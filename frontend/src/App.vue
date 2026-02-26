<script setup lang="ts">
import { RouterView } from 'vue-router'
import ToastManager from '@/components/ToastManager.vue'
import ConfirmationModal from '@/components/ConfirmationModal.vue'
</script>

<template>
  <RouterView />
  <ToastManager />
  <ConfirmationModal />
</template>
