<template>
  <div class="h-full">
    <div class="px-4 py-4 h-full overflow-y-auto">
      <!-- Header -->
      <div class="mb-4">
        <h1 class="text-lg font-bold text-gray-900">
          {{ __('Morant Consultores') }}
          <span v-if="workspace?.name" class="text-indigo-600">
            — {{ workspace.name }}
          </span>
        </h1>
        <p class="text-sm text-gray-600 mt-1">
          {{ __('') }}
        </p>
      </div>

      <!-- Filtro (Fechas + Botón) -->
      <div class="bg-white rounded-lg shadow-lg p-4 mb-4">
        <div class="flex flex-col lg:flex-row gap-4 lg:items-end">
          <div class="w-full lg:w-64">
            <p class="text-xs text-gray-600 mb-1">{{ __('Fecha inicio') }}</p>
            <Datepicker v-model="startDate" :enableTimePicker="false" autoApply>
              <template #trigger>
                <button
                  type="button"
                  class="w-full text-left px-3 py-2 border rounded-md bg-white hover:bg-gray-50 text-sm"
                >
                  {{ formatDate(startDate) }}
                </button>
              </template>
            </Datepicker>
          </div>

          <div class="w-full lg:w-64">
            <p class="text-xs text-gray-600 mb-1">{{ __('Fecha fin') }}</p>
            <Datepicker v-model="endDate" :enableTimePicker="false" autoApply>
              <template #trigger>
                <button
                  type="button"
                  class="w-full text-left px-3 py-2 border rounded-md bg-white hover:bg-gray-50 text-sm"
                >
                  {{ formatDate(endDate) }}
                </button>
              </template>
            </Datepicker>
          </div>

          <div class="flex gap-3 items-center">
            <button
              type="button"
              class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="loading"
              @click="generate"
            >
              {{ loading ? __('Generando...') : __('Generar') }}
            </button>

            <p v-if="kpiReady" class="text-xs text-gray-600">
              {{ rangeLabel }}
            </p>
          </div>
        </div>

        <p v-if="error" class="mt-3 text-sm text-red-600">{{ error }}</p>
      </div>

      <!-- ✅ Dropdown de Proyectos (checkboxs) -->
      <div class="bg-white rounded-lg shadow-lg p-4 mb-4">
        <div class="flex flex-col lg:flex-row gap-4 lg:items-center lg:justify-between">
          <div>
            <p class="text-sm font-semibold text-gray-900">{{ __('Filtro por proyectos') }}</p>
            <p class="text-xs text-gray-600 mt-1">
              {{ __('Selecciona qué proyectos aparecen como columnas.') }}
            </p>
          </div>

          <div class="relative" ref="projectsDropdown">
            <button
              type="button"
              class="px-4 py-2 rounded-md border bg-white hover:bg-gray-50 text-sm flex items-center gap-2"
              :disabled="loading || allProjects.length === 0"
              @click="toggleProjectsDropdown"
            >
              <span class="font-semibold">{{ __('Proyectos') }}</span>
              <span class="text-gray-500">
                ({{ selectedProjects.length }}/{{ allProjects.length }})
              </span>
              <svg class="w-4 h-4 ml-1" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" />
              </svg>
            </button>

            <!-- Dropdown -->
            <div
              v-if="projectsDropdownOpen"
              class="absolute right-0 mt-2 w-[360px] bg-white border rounded-lg shadow-xl z-50 overflow-hidden"
            >
              <div class="px-3 py-2 border-b flex items-center justify-between">
                <p class="text-xs font-semibold text-gray-700">{{ __('Seleccionar proyectos') }}</p>
                <button
                  type="button"
                  class="text-xs font-semibold text-indigo-600 hover:text-indigo-700"
                  @click="projectsDropdownOpen = false"
                >
                  {{ __('Cerrar') }}
                </button>
              </div>

              <div class="px-3 py-2 border-b flex gap-2">
                <button
                  type="button"
                  class="px-2 py-1 rounded border text-xs hover:bg-gray-50"
                  @click="selectAllProjects"
                >
                  {{ __('Seleccionar todo') }}
                </button>
                <button
                  type="button"
                  class="px-2 py-1 rounded border text-xs hover:bg-gray-50"
                  @click="clearProjectsSelection"
                >
                  {{ __('Limpiar') }}
                </button>
              </div>

              <div class="max-h-72 overflow-y-auto">
                <label
                  v-for="p in allProjects"
                  :key="p"
                  class="flex items-center gap-2 px-3 py-2 hover:bg-gray-50 cursor-pointer"
                >
                  <input
                    type="checkbox"
                    class="rounded border-gray-300"
                    :value="p"
                    v-model="selectedProjects"
                  />
                  <span class="text-sm text-gray-800">{{ p }}</span>
                </label>

                <div v-if="allProjects.length === 0" class="px-3 py-4 text-sm text-gray-500">
                  {{ __('No hay proyectos con actividad en este rango.') }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ✅ Tabla matriz (antes de la gráfica) -->
      <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-4">
        <div class="px-4 py-3 border-b">
          <h3 class="text-sm font-bold text-gray-900">{{ __('Horas por usuario y proyecto') }}</h3>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left text-xs font-semibold text-gray-600 px-4 py-3 w-64">{{ __('Usuario') }}</th>

                <th
                  v-for="p in tableProjects"
                  :key="p"
                  class="text-left text-xs font-semibold text-gray-600 px-4 py-3 whitespace-nowrap"
                >
                  {{ p }}
                </th>

                <th class="text-left text-xs font-semibold text-gray-600 px-4 py-3 w-32">
                  {{ __('Total') }}
                </th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="!loading && tableRows.length === 0">
                <td :colspan="2 + tableProjects.length" class="px-4 py-6 text-sm text-gray-500">
                  {{ __('No hay datos para el rango seleccionado.') }}
                </td>
              </tr>

              <tr v-for="r in tableRows" :key="r.usuario" class="border-t hover:bg-gray-50">
                <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ r.usuario }}</td>

                <td
                  v-for="p in tableProjects"
                  :key="p"
                  class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap"
                >
                  {{ Number(r.projects?.[p] || 0).toFixed(1) }}h
                </td>

                <td class="px-4 py-3 text-sm text-gray-900 font-semibold">
                  {{ Number(r.total || 0).toFixed(1) }}h
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ✅ Gráfica -->
      <div class="bg-white rounded-lg shadow-lg p-4">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-base font-bold text-gray-900">{{ __('Distribución de horas') }}</h3>
          <p class="text-xs text-gray-600" v-if="kpiReady">{{ rangeLabel }}</p>
        </div>
        <div ref="hoursByUserProjectChart" class="w-full" style="min-height: 520px;"></div>
      </div>
    </div>
  </div>
</template>

<script>
import Layout from '@/Shared/Layout'
import Datepicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import axios from 'axios'
import moment from 'moment'
import { nextTick } from 'vue'

export default {
  layout: Layout,
  components: { Datepicker },
  props: { workspace: Object },

  data() {
    const start = moment().startOf('week').add(1, 'day')
    const end = moment().endOf('week').add(1, 'day')

    return {
      startDate: start.toDate(),
      endDate: end.toDate(),
      loading: false,
      error: null,
      kpiReady: false,

      plotly: null,

      allProjects: [],
      selectedProjects: [],
      rawRows: [],

      projectsDropdownOpen: false,
    }
  },

  computed: {
    rangeLabel() {
      const s = moment(this.startDate).format('YYYY-MM-DD')
      const e = moment(this.endDate).format('YYYY-MM-DD')
      return `${s} → ${e}`
    },

    // Columnas visibles
    tableProjects() {
      return (this.selectedProjects || []).length ? this.selectedProjects : []
    },

    // Filas recalculadas con base en los proyectos seleccionados
    tableRows() {
      const cols = this.tableProjects
      if (!cols.length) return []

      return (this.rawRows || [])
        .map(r => {
          let total = 0
          const proj = {}

          cols.forEach(p => {
            const v = Number(r.projects?.[p] || 0)
            proj[p] = v
            total += v
          })

          return { ...r, projects: proj, total }
        })
        .sort((a, b) => Number(b.total || 0) - Number(a.total || 0))
    },
  },

  mounted() {
    document.addEventListener('click', this.onOutsideClick)

    import('plotly.js-dist-min')
      .then((m) => {
        this.plotly = m.default || m
        this.generate()
      })
      .catch(() => {
        this.error = 'No se pudo cargar Plotly.'
      })
  },

  beforeUnmount() {
    document.removeEventListener('click', this.onOutsideClick)
  },

  watch: {
    selectedProjects: {
      handler() {
        this.renderChart()
      },
      deep: true,
    },
  },

  methods: {
    formatDate(d) {
      return d ? moment(d).format('YYYY-MM-DD') : 'YYYY-MM-DD'
    },

    toggleProjectsDropdown() {
      this.projectsDropdownOpen = !this.projectsDropdownOpen
    },

    onOutsideClick(e) {
      if (!this.projectsDropdownOpen) return
      const el = this.$refs.projectsDropdown
      if (el && !el.contains(e.target)) this.projectsDropdownOpen = false
    },

    selectAllProjects() {
      this.selectedProjects = [...this.allProjects]
    },

    clearProjectsSelection() {
      this.selectedProjects = []
    },

    async generate() {
      this.error = null
      this.loading = true
      this.kpiReady = false

      // limpia para evitar “datos anteriores”
      this.rawRows = []
      this.allProjects = []
      this.selectedProjects = []

      try {
        const start = moment(this.startDate).format('YYYY-MM-DD')
        const end = moment(this.endDate).format('YYYY-MM-DD')

        const res = await axios.get(this.route('workspace.charts.general.hoursByUserProject'), {
          params: {
            workspace_id: this.workspace.id,
            start_date: start,
            end_date: end,
          },
        })

        this.allProjects = res.data?.projects || []
        this.rawRows = res.data?.rows || []

        // Default: selecciona todo
        this.selectedProjects = [...this.allProjects]

        this.kpiReady = true

        await nextTick()
        this.renderChart()
      } catch (e) {
        this.error =
          e?.response?.data?.error ||
          e?.response?.data?.message ||
          'Ocurrió un error al generar las estadísticas.'
      } finally {
        this.loading = false
      }
    },

    renderChart() {
      if (!this.plotly || !this.$refs.hoursByUserProjectChart) return

      const users = (this.tableRows || []).map(r => r.usuario)
      const cols = this.tableProjects || []

      // si no hay columnas o filas, limpia gráfica
      if (!cols.length || !users.length) {
        this.plotly.purge(this.$refs.hoursByUserProjectChart)
        return
      }

      const traces = cols.map(projectName => ({
        type: 'bar',
        orientation: 'h',
        name: projectName,
        y: users,
        x: (this.tableRows || []).map(r => Number(r.projects?.[projectName] || 0)),
        hovertemplate: 'Usuario: %{y}<br>Horas: %{x:.2f}<extra></extra>',
      }))

      const baseHeight = 180
      const rowHeight = 34
      const h = Math.max(520, baseHeight + (users.length * rowHeight))

      const layout = {
        barmode: 'group',
        height: h,
        margin: { l: 220, r: 20, t: 20, b: 40 },
        xaxis: { title: 'Horas', rangemode: 'tozero' },
        yaxis: { automargin: true },
        paper_bgcolor: 'rgba(0,0,0,0)',
        plot_bgcolor: 'rgba(0,0,0,0)',
        legend: { orientation: 'h', y: 1.05, x: 0, xanchor: 'left' },
      }

      const config = { responsive: true, displayModeBar: false }

      this.plotly.react(this.$refs.hoursByUserProjectChart, traces, layout, config)
    },
  },
}
</script>