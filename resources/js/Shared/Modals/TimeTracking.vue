<template>
  <div class="p-6 space-y-6">
    
    <!-- Header -->
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold text-gray-800">
        Logs de tiempos
      </h1>
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-600">
          <tr>
            <th class="px-6 py-3 text-left">Subcolumna</th>
            <th class="px-6 py-3 text-left">Límite (días)</th>
            <th class="px-6 py-3 text-left">Última ejecución</th>
            <th class="px-6 py-3 text-left">Estado</th>
            <th class="px-6 py-3 text-right">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <tr v-for="log in logs" :key="log.id" class="hover:bg-gray-50">
            <td class="px-6 py-4">{{ log.subcolumn }}</td>
            <td class="px-6 py-4">{{ log.days_limit }}</td>
            <td class="px-6 py-4 text-gray-500">{{ log.last_run }}</td>
            <td class="px-6 py-4">
              <span
                class="px-2 py-1 rounded-full text-xs font-medium"
                :class="log.active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
              >
                {{ log.active ? 'Activo' : 'Inactivo' }}
              </span>
            </td>
            <td class="px-6 py-4 text-right">
              <button
                @click="openModal(log)"
                class="text-blue-600 hover:underline text-sm"
              >
                Editar
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <transition name="fade">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="close"></div>

        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md p-6 z-10">
          <h2 class="text-lg font-semibold text-gray-800 mb-4">
            Editar límite de tiempo
          </h2>

          <form @submit.prevent="save" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-1">
                Límite en días
              </label>
              <input
                type="number"
                min="1"
                v-model="selected.days_limit"
                class="w-full rounded-lg border px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <div class="flex justify-end gap-3 pt-4">
              <button
                type="button"
                @click="close"
                class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg"
              >
                Cancelar
              </button>
              <button
                type="submit"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm"
              >
                Guardar
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>

  </div>
</template>
<script setup>
import { ref } from 'vue'

const logs = ref([
  {
    id: 1,
    subcolumn: 'Pendientes',
    days_limit: 7,
    last_run: '2026-01-28 10:30',
    active: true,
  },
  {
    id: 2,
    subcolumn: 'Archivados',
    days_limit: 30,
    last_run: '2026-01-27 23:00',
    active: false,
  },
])

const showModal = ref(false)
const selected = ref({})

const openModal = (log) => {
  selected.value = { ...log }
  showModal.value = true
}

const close = () => {
  showModal.value = false
}

const save = () => {
  const index = logs.value.findIndex(l => l.id === selected.value.id)
  if (index !== -1) {
    logs.value[index].days_limit = selected.value.days_limit
  }
  close()
}
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
</style>
