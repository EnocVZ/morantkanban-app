<template>
    <button
    class="bg-indigo-600 w-full text-white p-[9px] rounded flex items-center justify-center gap-2 disabled:opacity-50"
    :disabled="loading"
    @click="$emit('click')"
  >
    <svg v-if="loading" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
    </svg>
    <span v-if="!loading">{{ __(label) }}</span>
    <span v-else>{{loaderLabel}}...</span>
  </button>
</template>
<script>
export default {
    name: 'shared-button',
    props: {
        loading: {
            type: Boolean,
            default: false
        },
        label: {
            type: String,
            default: 'Submit'
        },
        loaderLabel: {
            type: String,
            default: 'Cargando'
        }
    },
    emits: ['click'],
   
}
</script>
