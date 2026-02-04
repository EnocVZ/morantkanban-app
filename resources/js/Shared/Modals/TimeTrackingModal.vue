<template>
  <!-- Overlay -->
  <transition name="fade">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
      @click.self="close"
    >
      <!-- Modal -->
      <transition name="scale">
        <div
          class="w-full max-w-xl bg-white rounded-xl shadow-xl p-6"
        >
          <!-- Header -->
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-slate-800">
              Seguimiento del tiempo
            </h2>
            <button
              @click="close"
              class="text-slate-400 hover:text-slate-600"
            >
              ✕
            </button>
          </div>

          <!-- CONTENT -->
          <slot />

        </div>
      </transition>
    </div>
  </transition>
</template>
<script setup>
defineProps({
  open: Boolean
})

const emit = defineEmits(['close'])

const close = () => emit('close')
</script>
<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.scale-enter-active,
.scale-leave-active {
  transition: all 0.2s ease;
}
.scale-enter-from,
.scale-leave-to {
  opacity: 0;
  transform: scale(0.95);
}
</style>
