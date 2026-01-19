<template>
  <div class="h-full">
    <Head :title="__(title)" />
    <board-view-menu :project="project" view="statistics" />

    <div class="px-4 py-4 h-[calc(100%-52px)] overflow-y-auto">
      <div class="bg-white rounded-lg shadow-lg p-6">
        <h3 class="text-lg font-bold mb-2">{{ __('Estadísticas') }}</h3>
        <p class="text-sm text-gray-600">
          {{ __('Pantalla en construcción: aquí colocaremos las métricas y gráficas del tablero.') }}
        </p>
      </div>
    </div>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout'
import BoardViewMenu from '@/Shared/BoardViewMenu'

export default {
  components: { Head, BoardViewMenu },
  layout: Layout,
  props: { title: String, auth: Object, project: Object },
}
</script>
