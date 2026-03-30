<template>
  <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center">
    
    <!-- Overlay -->
    <div 
      class="absolute inset-0 bg-black/40 backdrop-blur-sm"
      @click="onCancel"
    ></div>

    <!-- Modal -->
    <div
      class="relative bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6
             transform transition-all duration-200 scale-100 opacity-100"
      role="dialog"
      aria-modal="true"
    >
      <!-- Icon -->
      <div class="flex items-center justify-center mb-4">
        <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
          <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" stroke-width="2"
            viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M12 9v2m0 4h.01M5.455 19h13.09c1.054 0 1.707-1.14 1.18-2.053L13.18 4.947c-.527-.913-1.833-.913-2.36 0L4.275 16.947C3.748 17.86 4.401 19 5.455 19z"/>
          </svg>
        </div>
      </div>

      <!-- Title -->
      <h2 class="text-lg font-semibold text-gray-900 text-center">
        {{ title }}
      </h2>

      <!-- Message -->
      <p class="text-sm text-gray-500 text-center mt-2">
        {{ message }}
      </p>

      <!-- Actions -->
      <div class="mt-6 flex justify-end gap-3">
        <button
          @click="onCancel"
          class="px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 text-gray-700
                 hover:bg-gray-100 transition"
        >
          Cancelar
        </button>
        <loading-button :loading="loaderConfirm"
            class="px-4 py-2 text-sm font-medium rounded-lg bg-red-600 text-white
                 hover:bg-red-700 transition shadow-sm"
            @click="onConfirm">
           Confirmar
        </loading-button>
      </div>
    </div>
  </div>
</template>

<script>
import LoadingButton from '@/Shared/LoadingButton'

export default {
    components: {
        LoadingButton,
    },
  props: {
    modelValue: Boolean,
    title: {
      type: String,
      default: '¿Estás seguro?'
    },
    message: {
      type: String,
      default: 'Esta acción no se puede deshacer.'
    },
    loaderConfirm: {
      type: Boolean,
      default: false
    }   
  },
  emits: ['update:modelValue', 'confirm'],

  methods: {
    onCancel() {
      this.$emit('update:modelValue', false)
    },
    onConfirm() {
      this.$emit('confirm')
    }
  }
}
</script>