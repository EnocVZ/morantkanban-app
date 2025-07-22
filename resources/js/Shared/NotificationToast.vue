<template>
  <div class="fixed top-5 right-5 z-50 space-y-2">
    <transition-group name="toast" tag="div">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        :class="[
          'max-w-sm w-full px-4 py-3 rounded-lg shadow-lg flex items-center justify-between text-white',
          toast.type === 'success' ? 'bg-green-600' : '',
          toast.type === 'error' ? 'bg-red-600' : '',
          toast.type === 'info' ? 'bg-blue-600' : '',
          toast.type === 'warning' ? 'bg-yellow-500 text-black' : ''
        ]"
      >
        <span>{{ toast.message }}</span>
        <button @click="removeToast(toast.id)" class="ml-2">&times;</button>
      </div>
    </transition-group>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const toasts = ref([])

function showToast(message, type = 'info', duration = 4000) {
  const id = Date.now()
  toasts.value.push({ id, message, type })
  setTimeout(() => removeToast(id), duration)
}

function removeToast(id) {
  toasts.value = toasts.value.filter(t => t.id !== id)
}

// Exponer para usarlo globalmente
defineExpose({ showToast })
</script>

<style scoped>
.toast-enter-active, .toast-leave-active {
  transition: all 0.3s ease;
}
.toast-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}
.toast-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
