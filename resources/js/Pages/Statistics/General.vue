<template>
  <div class="p-6">
    <h1 class="text-xl font-bold text-gray-900">{{ __('Estadísticas generales') }}</h1>
    <p class="text-sm text-gray-600 mt-2">
      {{ __('Aquí irá el dashboard general.') }}
    </p>
  </div>
</template>

<script>
import Layout from '@/Shared/Layout'

export default {
  layout: Layout,
  props: {
    title: String,
    auth: Object,
  },
}
</script>