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
        <p class="text-sm text-gray-600 mt-1"></p>
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

      <!-- ✅ Filtro dinámico -->
      <div class="bg-white rounded-lg shadow-lg p-4 mb-4">
        <div class="flex flex-col lg:flex-row gap-4 lg:items-center lg:justify-between">
          <div>
            <p class="text-sm font-semibold text-gray-900">{{ __('Filtro de columnas') }}</p>
            <p class="text-xs text-gray-600 mt-1">
              {{ __('Selecciona si las tablas y la gráfica se mostrarán por proyecto, etiqueta o carril.') }}
            </p>
          </div>

          <div class="flex flex-col lg:flex-row gap-3 lg:items-center w-full lg:w-auto">
            <!-- Radios -->
            <div class="flex flex-wrap items-center gap-4">
              <label class="flex items-center gap-2 text-sm text-gray-800 cursor-pointer">
                <input
                  type="radio"
                  value="project"
                  v-model="groupBy"
                  :disabled="loading"
                />
                <span>{{ __('Proyecto') }}</span>
              </label>

              <label class="flex items-center gap-2 text-sm text-gray-800 cursor-pointer">
                <input
                  type="radio"
                  value="label"
                  v-model="groupBy"
                  :disabled="loading"
                />
                <span>{{ __('Etiqueta') }}</span>
              </label>

              <label class="flex items-center gap-2 text-sm text-gray-800 cursor-pointer">
                <input
                  type="radio"
                  value="lane"
                  v-model="groupBy"
                  :disabled="loading"
                />
                <span>{{ __('Carril') }}</span>
              </label>
            </div>

            <!-- Dropdown -->
            <div class="relative" ref="filterDropdown">
              <button
                type="button"
                class="px-4 py-2 rounded-md border bg-white hover:bg-gray-50 text-sm flex items-center gap-2"
                :disabled="loading || allItems.length === 0"
                @click.stop="toggleFilterDropdown"
              >
                <span class="font-semibold">{{ dimensionLabel }}</span>
                <span class="text-gray-500">({{ selectedItems.length }}/{{ allItems.length }})</span>
                <svg class="w-4 h-4 ml-1" viewBox="0 0 20 20" fill="currentColor">
                  <path
                    fill-rule="evenodd"
                    d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z"
                    clip-rule="evenodd"
                  />
                </svg>
              </button>

              <div
                v-if="filterDropdownOpen"
                class="absolute right-0 mt-2 w-[380px] bg-white border rounded-lg shadow-xl z-50 overflow-hidden"
                @click.stop
              >
                <div class="px-3 py-2 border-b flex items-center justify-between">
                  <p class="text-sm font-semibold text-gray-900">{{ dimensionLabel }}</p>

                  <div class="flex gap-2">
                    <button
                      type="button"
                      class="text-xs px-2 py-1 rounded border hover:bg-gray-50"
                      @click="selectAllItems"
                      :disabled="allItems.length === 0"
                    >
                      {{ __('Seleccionar todo') }}
                    </button>
                    <button
                      type="button"
                      class="text-xs px-2 py-1 rounded border hover:bg-gray-50"
                      @click="clearItemsSelection"
                      :disabled="allItems.length === 0"
                    >
                      {{ __('Limpiar') }}
                    </button>
                  </div>
                </div>

                <div class="max-h-[280px] overflow-y-auto">
                  <div v-if="allItems.length === 0" class="px-3 py-4 text-sm text-gray-500">
                    {{ __('No hay elementos para mostrar. Genera primero.') }}
                  </div>

                  <label
                    v-for="item in allItems"
                    :key="item"
                    class="flex items-center gap-2 px-3 py-2 hover:bg-gray-50 cursor-pointer"
                  >
                    <input
                      type="checkbox"
                      class="rounded border-gray-300"
                      :checked="selectedItems.includes(item)"
                      @change="toggleItem(item)"
                    />
                    <span class="text-sm text-gray-800">{{ item }}</span>
                  </label>
                </div>

                <div class="px-3 py-2 border-t flex items-center justify-between">
                  <p class="text-xs text-gray-500">{{ __('Columnas visibles:') }} {{ selectedItems.length }}</p>
                  <button
                    type="button"
                    class="text-xs px-2 py-1 rounded border hover:bg-gray-50"
                    @click="filterDropdownOpen = false"
                  >
                    {{ __('Cerrar') }}
                  </button>
                </div>
              </div>
            </div>

            <!-- Export matriz -->
            <button
              type="button"
              :disabled="loading || !kpiReady || selectedItems.length === 0"
              @click="exportExcel"
              class="inline-flex items-center justify-center gap-2 h-10 px-4 rounded-md font-semibold text-sm whitespace-nowrap disabled:opacity-50 disabled:cursor-not-allowed"
              style="background:#059669;color:#ffffff;border:1px solid rgba(0,0,0,.08);"
            >
              {{ __('Exportar Excel') }}
            </button>
          </div>
        </div>
      </div>

      <!-- ✅ Tabla matriz -->
      <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-4">
        <div class="px-4 py-3 border-b flex items-center justify-between">
          <div>
            <h3 class="text-sm font-bold text-gray-900">
              {{ matrixTitle }}
            </h3>
            <p class="text-xs text-gray-600 mt-1">
              {{ __('Top ') }}{{ TOP_MATRIX_USERS }}{{ __(' usuarios por horas. ') }}
              <span v-if="kpiReady">{{ rangeLabel }}</span>
            </p>
          </div>

          <div class="flex items-center gap-2">
            <button
              v-if="tableRows.length > TOP_MATRIX_USERS"
              type="button"
              class="text-xs px-3 py-1.5 rounded-md border bg-white hover:bg-gray-50"
              @click="showAllMatrix = !showAllMatrix"
              :disabled="loading || !kpiReady"
            >
              {{ showAllMatrix ? __('Ver menos') : __('Ver todos') }}
              <span class="text-gray-500">
                ({{ showAllMatrix ? tableRows.length : Math.min(TOP_MATRIX_USERS, tableRows.length) }}/{{ tableRows.length }})
              </span>
            </button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left text-xs font-semibold text-gray-600 px-4 py-3 w-64">{{ __('Usuario') }}</th>

                <th
                  v-for="col in tableColumns"
                  :key="col"
                  class="text-left text-xs font-semibold text-gray-600 px-4 py-3 whitespace-nowrap"
                >
                  {{ col }}
                </th>

                <th class="text-left text-xs font-semibold text-gray-600 px-4 py-3 w-32">
                  {{ __('Total') }}
                </th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="!loading && tableRowsToShow.length === 0">
                <td :colspan="2 + tableColumns.length" class="px-4 py-6 text-sm text-gray-500">
                  {{ __('No hay datos para el rango seleccionado o no hay columnas seleccionadas.') }}
                </td>
              </tr>

              <tr v-for="r in tableRowsToShow" :key="r.usuario" class="border-t hover:bg-gray-50">
                <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ r.usuario }}</td>

                <td v-for="col in tableColumns" :key="col" class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">
                  {{ Number(r.items?.[col] || 0).toFixed(1) }}h
                </td>

                <td class="px-4 py-3 text-sm text-gray-900 font-semibold">
                  {{ Number(r.total || 0).toFixed(1) }}h
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ✅ Tabla DETALLE -->
      <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-4">
        <div class="px-4 py-3 border-b flex items-center justify-between">
          <div>
            <h3 class="text-sm font-bold text-gray-900">{{ __('Detalle de tareas') }}</h3>
            <p class="text-xs text-gray-600 mt-1">
              {{ __('Top ') }}{{ TOP_DETAIL_ROWS }}{{ __(' registros (según selección actual).') }}
            </p>
          </div>

          <div class="flex items-center gap-2">
            <button
              v-if="detailRows.length > TOP_DETAIL_ROWS"
              type="button"
              class="text-xs px-3 py-1.5 rounded-md border bg-white hover:bg-gray-50"
              @click="showAllDetail = !showAllDetail"
              :disabled="loading || !kpiReady || loadingDetail"
            >
              {{ showAllDetail ? __('Ver menos') : __('Ver todos') }}
              <span class="text-gray-500">
                ({{ showAllDetail ? detailRows.length : Math.min(TOP_DETAIL_ROWS, detailRows.length) }}/{{ detailRows.length }})
              </span>
            </button>

            <button
              type="button"
              class="inline-flex items-center justify-center gap-2 h-9 px-3 rounded-md font-semibold text-sm disabled:opacity-50 disabled:cursor-not-allowed"
              style="background:#059669;color:#ffffff;border:1px solid rgba(0,0,0,.08);"
              :disabled="loading || !kpiReady || loadingDetail"
              @click="exportDetailExcel"
            >
              {{ __('Exportar detalle') }}
            </button>
          </div>
        </div>

        <div class="px-4 py-2" v-if="loadingDetail">
          <div class="text-xs text-gray-500">{{ __('Cargando detalle...') }}</div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left text-xs font-semibold text-gray-600 px-4 py-3 whitespace-nowrap">{{ __('Espacio') }}</th>
                <th class="text-left text-xs font-semibold text-gray-600 px-4 py-3 whitespace-nowrap">{{ __('Proyecto') }}</th>
                <th class="text-left text-xs font-semibold text-gray-600 px-4 py-3 whitespace-nowrap">{{ __('Tarea') }}</th>
                <th class="text-left text-xs font-semibold text-gray-600 px-4 py-3 whitespace-nowrap">{{ __('Terminada') }}</th>
                <th class="text-left text-xs font-semibold text-gray-600 px-4 py-3 whitespace-nowrap">{{ __('Creación') }}</th>
                <th class="text-left text-xs font-semibold text-gray-600 px-4 py-3 whitespace-nowrap">{{ __('Realizada por') }}</th>
                <th class="text-left text-xs font-semibold text-gray-600 px-4 py-3 whitespace-nowrap">{{ __('Inicio') }}</th>
                <th class="text-left text-xs font-semibold text-gray-600 px-4 py-3 whitespace-nowrap">{{ __('Fin') }}</th>
                <th class="text-left text-xs font-semibold text-gray-600 px-4 py-3 whitespace-nowrap">{{ __('Horas') }}</th>
                <th class="text-left text-xs font-semibold text-gray-600 px-4 py-3 whitespace-nowrap">{{ __('Carril') }}</th>
                <th class="text-left text-xs font-semibold text-gray-600 px-4 py-3 whitespace-nowrap">{{ __('Estatus') }}</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="!loadingDetail && detailRowsToShow.length === 0">
                <td colspan="11" class="px-4 py-6 text-sm text-gray-500">
                  {{ __('No hay datos de detalle para el rango seleccionado o con la selección actual.') }}
                </td>
              </tr>

              <tr v-for="(r, idx) in detailRowsToShow" :key="idx" class="border-t hover:bg-gray-50">
                <td class="px-4 py-3 text-sm text-gray-800 whitespace-nowrap">{{ r.espacio }}</td>
                <td class="px-4 py-3 text-sm text-gray-800 whitespace-nowrap">{{ r.proyecto }}</td>
                <td class="px-4 py-3 text-sm text-gray-900 font-medium whitespace-nowrap">{{ r.tarea }}</td>
                <td class="px-4 py-3 text-sm text-gray-800 whitespace-nowrap">
                  <span
                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold"
                    :class="Number(r.terminada) ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-700'"
                  >
                    {{ Number(r.terminada) ? __('Sí') : __('No') }}
                  </span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-800 whitespace-nowrap">{{ fmtDateTime(r.creacion) }}</td>
                <td class="px-4 py-3 text-sm text-gray-800 whitespace-nowrap">{{ r.realizada_por }}</td>
                <td class="px-4 py-3 text-sm text-gray-800 whitespace-nowrap">{{ fmtDateTime(r.inicio) }}</td>
                <td class="px-4 py-3 text-sm text-gray-800 whitespace-nowrap">{{ fmtDateTime(r.fin) }}</td>
                <td class="px-4 py-3 text-sm text-gray-900 font-semibold whitespace-nowrap">{{ Number(r.horas || 0).toFixed(2) }}</td>
                <td class="px-4 py-3 text-sm text-gray-800 whitespace-nowrap">{{ r.carril }}</td>
                <td class="px-4 py-3 text-sm text-gray-800 whitespace-nowrap">{{ r.estatus }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ✅ GRÁFICAS AL FINAL -->

      <!-- ✅ Gráfica horas -->
      <div class="bg-white rounded-lg shadow-lg p-4 mb-4">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-base font-bold text-gray-900">{{ __('Distribución de horas') }}</h3>
          <p class="text-xs text-gray-600" v-if="kpiReady">{{ rangeLabel }}</p>
        </div>
        <div ref="hoursByUserProjectChart" class="w-full" style="min-height: 520px;"></div>
      </div>

      <!-- ✅ GRAFICA % POR WORKSPACE -->
      <div class="bg-white rounded-lg shadow-lg p-4 mb-4">
        <div class="flex items-center justify-between mb-3">
          <div>
            <h3 class="text-base font-bold text-gray-900">{{ __('Porcentaje por espacio de trabajo') }}</h3>
            <p class="text-xs text-gray-600" v-if="kpiReady">{{ rangeLabel }}</p>
          </div>
          <p v-if="workspacePctError" class="text-xs text-red-600">{{ workspacePctError }}</p>
        </div>

        <div v-if="loadingWorkspacePct" class="text-xs text-gray-500 mb-2">
          {{ __('Cargando gráfica por workspace...') }}
        </div>

        <div ref="workspaceTimePctChart" class="w-full" style="min-height: 420px;"></div>
      </div>

      <!-- ✅ Heatmap: tiempo total trabajado -->
      <div class="bg-white rounded-lg shadow-lg p-4 mb-4">
        <div class="flex items-center justify-between mb-3">
          <div>
            <h3 class="text-base font-bold text-gray-900">{{ __('Tiempo total trabajado') }}</h3>
            <p class="text-xs text-gray-600" v-if="kpiReady">{{ rangeLabel }}</p>
          </div>
          <p v-if="heatmapError" class="text-xs text-red-600">{{ heatmapError }}</p>
        </div>

        <div v-if="loadingHeatmap" class="text-xs text-gray-500 mb-2">
          {{ __('Cargando heatmap...') }}
        </div>

        <div ref="userDailyHoursHeatmap" class="w-full" style="min-height: 520px;"></div>
      </div>

      <!-- ✅ Barras agrupadas: tareas por proyecto por día -->
      <div class="bg-white rounded-lg shadow-lg p-4 mb-4">
        <div class="flex items-center justify-between mb-3">
          <div>
            <h3 class="text-base font-bold text-gray-900">{{ __('Tareas por proyecto (por día)') }}</h3>
            <p class="text-xs text-gray-600" v-if="kpiReady">{{ rangeLabel }}</p>
          </div>
          <p v-if="projectTasksError" class="text-xs text-red-600">{{ projectTasksError }}</p>
        </div>

        <div v-if="loadingProjectTasks" class="text-xs text-gray-500 mb-2">
          {{ __('Cargando gráfica de tareas por proyecto...') }}
        </div>

        <div ref="projectTasksByDayChart" class="w-full" style="min-height: 520px;"></div>
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
import 'moment/locale/es'
moment.locale('es')

export default {
  layout: Layout,
  components: { Datepicker },
  props: { workspace: Object },

  data() {
    const start = moment().startOf('week').add(1, 'day')
    const end = moment().endOf('week').add(1, 'day')

    return {
      TOP_MATRIX_USERS: 8,
      TOP_DETAIL_ROWS: 8,

      showAllMatrix: false,
      showAllDetail: false,

      startDate: start.toDate(),
      endDate: end.toDate(),
      loading: false,
      loadingDetail: false,
      loadingWorkspacePct: false,

      error: null,
      kpiReady: false,

      plotly: null,

      groupBy: 'project',

      allItems: [],
      selectedItems: [],
      rawRows: [],

      detailRawRows: [],

      workspacePctRows: [],
      workspacePctError: null,

      filterDropdownOpen: false,

      loadingHeatmap: false,
      heatmapError: null,
      heatmapPayload: null,

      loadingProjectTasks: false,
      projectTasksError: null,
      projectTasksPayload: null,
    }
  },

  computed: {
    TOP_MATRIX_USERS() { return this.$data.TOP_MATRIX_USERS },
    TOP_DETAIL_ROWS() { return this.$data.TOP_DETAIL_ROWS },

    rangeLabel() {
      const s = moment(this.startDate).format('YYYY-MM-DD')
      const e = moment(this.endDate).format('YYYY-MM-DD')
      return `${s} → ${e}`
    },

    dimensionLabel() {
      if (this.groupBy === 'label') return this.__('Etiquetas')
      if (this.groupBy === 'lane') return this.__('Carriles')
      return this.__('Proyectos')
    },

    matrixTitle() {
      if (this.groupBy === 'label') return this.__('Horas por usuario y etiqueta')
      if (this.groupBy === 'lane') return this.__('Horas por usuario y carril')
      return this.__('Horas por usuario y proyecto')
    },

    tableColumns() {
      return (this.selectedItems || []).length ? this.selectedItems : []
    },

    tableRows() {
      const cols = this.tableColumns
      if (!cols.length) return []

      return (this.rawRows || [])
        .map(r => {
          let total = 0
          const items = {}

          cols.forEach(col => {
            const v = Number(r.items?.[col] || 0)
            items[col] = v
            total += v
          })

          return { ...r, items, total }
        })
        .sort((a, b) => Number(b.total || 0) - Number(a.total || 0))
    },

    tableRowsToShow() {
      const rows = this.tableRows || []
      if (this.showAllMatrix) return rows
      return rows.slice(0, this.TOP_MATRIX_USERS)
    },

    detailRows() {
      return this.detailRawRows || []
    },

    detailRowsToShow() {
      const rows = this.detailRows || []
      if (this.showAllDetail) return rows
      return rows.slice(0, this.TOP_DETAIL_ROWS)
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
    selectedItems: {
      async handler() {
        this.renderChart()

        if (this.kpiReady) {
          await this.loadDetailTable()
        }
      },
      deep: true,
    },

    async groupBy() {
      this.filterDropdownOpen = false
      this.showAllMatrix = false
      this.showAllDetail = false

      if (!this.kpiReady) return

      await this.loadMainDataset()
      await this.loadDetailTable()
    },
  },

  methods: {
    formatDate(d) {
      return d ? moment(d).format('YYYY-MM-DD') : 'YYYY-MM-DD'
    },

    fmtDateTime(v) {
      if (!v) return ''
      return moment(v).format('YYYY-MM-DD HH:mm')
    },

    toggleFilterDropdown() {
      this.filterDropdownOpen = !this.filterDropdownOpen
    },

    onOutsideClick(e) {
      if (!this.filterDropdownOpen) return
      const el = this.$refs.filterDropdown
      if (el && !el.contains(e.target)) this.filterDropdownOpen = false
    },

    toggleItem(item) {
      const idx = this.selectedItems.indexOf(item)
      if (idx >= 0) {
        const copy = [...this.selectedItems]
        copy.splice(idx, 1)
        this.selectedItems = copy
      } else {
        this.selectedItems = [...this.selectedItems, item]
      }
    },

    selectAllItems() {
      this.selectedItems = [...this.allItems]
    },

    clearItemsSelection() {
      this.selectedItems = []
    },

    async loadMainDataset() {
      const start = moment(this.startDate).format('YYYY-MM-DD')
      const end = moment(this.endDate).format('YYYY-MM-DD')

      let routeName = 'workspace.charts.general.hoursByUserProject'

      if (this.groupBy === 'label') {
        routeName = 'workspace.charts.general.hoursByUserLabel'
      } else if (this.groupBy === 'lane') {
        routeName = 'workspace.charts.general.hoursByUserLane'
      }

      const res = await axios.get(this.route(routeName), {
        params: {
          workspace_id: this.workspace.id,
          start_date: start,
          end_date: end,
        },
      })

      this.allItems = res.data?.dimensions || []
      this.rawRows = res.data?.rows || []
      this.selectedItems = [...this.allItems]

      await nextTick()
      this.renderChart()
    },

    async generate() {
      this.error = null
      this.workspacePctError = null
      this.heatmapError = null

      this.loading = true
      this.kpiReady = false

      this.showAllMatrix = false
      this.showAllDetail = false

      this.rawRows = []
      this.allItems = []
      this.selectedItems = []
      this.detailRawRows = []
      this.workspacePctRows = []
      this.heatmapPayload = null

      try {
        await this.loadMainDataset()

        this.kpiReady = true

        await this.loadDetailTable()
        await this.loadWorkspacePctChart()
        await this.loadUserDailyHeatmap()
        await this.loadProjectTasksByDay()
      } catch (e) {
        this.error =
          e?.response?.data?.error ||
          e?.response?.data?.message ||
          'Ocurrió un error al generar las estadísticas.'
      } finally {
        this.loading = false
      }
    },

    async loadDetailTable() {
      this.loadingDetail = true
      try {
        const start = moment(this.startDate).format('YYYY-MM-DD')
        const end = moment(this.endDate).format('YYYY-MM-DD')

        const res = await axios.get(
          this.route('workspace.statistics.general.table.taskTimersByDimension', this.workspace.slug || this.workspace.id),
          {
            params: {
              workspace_id: this.workspace.id,
              start_date: start,
              end_date: end,
              dimension_type: this.groupBy,
              selected_items: this.selectedItems,
            },
          }
        )

        this.detailRawRows = res.data?.rows || []
      } catch (e) {
        this.error =
          e?.response?.data?.error ||
          e?.response?.data?.message ||
          'No se pudo cargar la tabla de detalle.'
      } finally {
        this.loadingDetail = false
      }
    },

    async loadWorkspacePctChart() {
      if (!this.plotly) return
      this.loadingWorkspacePct = true
      this.workspacePctError = null

      try {
        const start = moment(this.startDate).format('YYYY-MM-DD')
        const end = moment(this.endDate).format('YYYY-MM-DD')

        const res = await axios.get(this.route('workspace.charts.general.workspacesTimePct'), {
          params: { start_date: start, end_date: end },
        })

        this.workspacePctRows = res.data?.rows || []

        await nextTick()
        this.renderWorkspacePctChart()
      } catch (e) {
        this.workspacePctError =
          e?.response?.data?.error ||
          e?.response?.data?.message ||
          'No se pudo cargar la gráfica por workspace.'
      } finally {
        this.loadingWorkspacePct = false
      }
    },

    renderChart() {
      if (!this.plotly || !this.$refs.hoursByUserProjectChart) return

      const users = (this.tableRows || []).map(r => r.usuario)
      const cols = this.tableColumns || []

      if (!cols.length || !users.length) {
        this.plotly.purge(this.$refs.hoursByUserProjectChart)
        return
      }

      const traces = cols.map(itemName => ({
        type: 'bar',
        orientation: 'h',
        name: itemName,
        y: users,
        x: (this.tableRows || []).map(r => Number(r.items?.[itemName] || 0)),
        hovertemplate: 'Usuario: %{y}<br>' + this.dimensionLabel.slice(0, -1) + ': %{fullData.name}<br>Horas: %{x:.2f}<extra></extra>',
      }))

      const baseHeight = 180
      const rowHeight = 34
      const h = Math.max(520, baseHeight + (users.length * rowHeight))

      const layout = {
        barmode: 'stack',
        height: h,
        margin: { l: 220, r: 20, t: 20, b: 40 },
        xaxis: {
          title: 'Horas',
          rangemode: 'tozero',
        },
        yaxis: {
          automargin: true,
          autorange: 'reversed',
        },
        paper_bgcolor: 'rgba(0,0,0,0)',
        plot_bgcolor: 'rgba(0,0,0,0)',
        legend: {
          orientation: 'h',
          y: 1.08,
          x: 0,
          xanchor: 'left',
        },
      }

      const config = {
        responsive: true,
        displayModeBar: false,
      }

      this.plotly.react(this.$refs.hoursByUserProjectChart, traces, layout, config)
    },

    renderWorkspacePctChart() {
      if (!this.plotly || !this.$refs.workspaceTimePctChart) return

      const rows = [...(this.workspacePctRows || [])].sort((a, b) => Number(b.pct) - Number(a.pct))

      if (!rows.length) {
        this.plotly.purge(this.$refs.workspaceTimePctChart)
        return
      }

      const y = rows.map(r => r.workspace)
      const x = rows.map(r => Number(r.pct || 0))

      const trace = {
        type: 'bar',
        orientation: 'h',
        y,
        x,
        hovertemplate: '%{y}<br>%{x:.2f}%<extra></extra>',
        text: x.map(v => `${Number(v).toFixed(2)}%`),
        textposition: 'auto',
      }

      const height = Math.max(420, 140 + rows.length * 32)

      const layout = {
        height,
        margin: { l: 220, r: 20, t: 10, b: 40 },
        xaxis: { title: 'Porcentaje (%)', range: [0, 100], rangemode: 'tozero' },
        yaxis: { automargin: true },
        paper_bgcolor: 'rgba(0,0,0,0)',
        plot_bgcolor: 'rgba(0,0,0,0)',
      }

      const config = { responsive: true, displayModeBar: false }

      this.plotly.react(this.$refs.workspaceTimePctChart, [trace], layout, config)
    },

    async exportExcel() {
    try {
      const start = moment(this.startDate).format('YYYY-MM-DD')
      const end = moment(this.endDate).format('YYYY-MM-DD')

      const res = await axios.get(
        this.route('workspace.statistics.general.export.reportByDimension', this.workspace.slug || this.workspace.id),
        {
          params: {
            workspace_id: this.workspace.id,
            start_date: start,
            end_date: end,
            dimension_type: this.groupBy,
            selected_items: this.selectedItems,
          },
          responseType: 'blob',
        }
      )

      const blob = new Blob([res.data], {
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      })

      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url

      const filename = `reporte_${this.groupBy}_${start.replaceAll('-', '')}_${end.replaceAll('-', '')}.xlsx`
      a.download = filename

      document.body.appendChild(a)
      a.click()
      a.remove()
      window.URL.revokeObjectURL(url)
    } catch (e) {
      this.error = e
        // e?.response?.data?.error ||
        // e?.response?.data?.message ||
        // 'No se pudo exportar el Excel.'
    }
    },

  async exportDetailExcel() {
    await this.exportExcel()
  },

    async loadUserDailyHeatmap() {
      if (!this.plotly) return
      this.loadingHeatmap = true
      this.heatmapError = null
      this.heatmapPayload = null

      try {
        const start = moment(this.startDate).format('YYYY-MM-DD')
        const end = moment(this.endDate).format('YYYY-MM-DD')

        const res = await axios.get(this.route('workspace.charts.general.userDailyHoursHeatmap'), {
          params: {
            start_date: start,
            end_date: end,
          },
        })

        this.heatmapPayload = res.data || null

        await nextTick()
        this.renderUserDailyHeatmap()
      } catch (e) {
        this.heatmapError =
          e?.response?.data?.error ||
          e?.response?.data?.message ||
          'No se pudo cargar el heatmap.'
      } finally {
        this.loadingHeatmap = false
      }
    },

    renderUserDailyHeatmap() {
      if (!this.plotly || !this.$refs.userDailyHoursHeatmap) return
      const payload = this.heatmapPayload

      const users = payload?.users || []
      const dates = payload?.dates || []
      const z = payload?.z || []

      if (!users.length || !dates.length || !z.length) {
        this.plotly.purge(this.$refs.userDailyHoursHeatmap)
        return
      }

      const weekendIdx = new Set(
        dates
          .map((d, i) => ({ d, i }))
          .filter(({ d }) => {
            const day = moment(d, 'YYYY-MM-DD').day()
            return day === 0 || day === 6
          })
          .map(({ i }) => i)
      )

      const zMasked = z.map(row =>
        row.map((val, j) => (weekendIdx.has(j) ? null : Number(val)))
      )

      const textMasked = z.map(row =>
        row.map((val, j) => {
          if (weekendIdx.has(j)) return ''
          const n = Number(val)
          return n === 0 ? '' : n.toFixed(1)
        })
      )

      const tickvals = dates
      const ticktext = dates.map(d => moment(d, 'YYYY-MM-DD').format('DD MMM'))

      const height = Math.max(520, 170 + users.length * 28)

      const midScale = [
        [0.0, '#f2f4f7'],
        [0.15, '#e3edf7'],
        [0.35, '#c8def0'],
        [0.55, '#9fc5e8'],
        [0.75, '#6ea8dc'],
        [1.0, '#3b82f6'],
      ]

      const trace = {
        type: 'heatmap',
        x: dates,
        y: users,
        z: zMasked,
        text: textMasked,
        texttemplate: '%{text}',
        textfont: { size: 11 },
        hoverongaps: false,
        hovertemplate: 'Usuario: %{y}<br>Fecha: %{x}<br>Horas: %{z:.2f}<extra></extra>',
        colorscale: midScale,
        zmin: 0,
        colorbar: { title: 'Horas' },
      }

      const layout = {
        height,
        margin: { l: 240, r: 40, t: 10, b: 90 },
        xaxis: {
          title: 'Fecha',
          tickmode: 'array',
          tickvals,
          ticktext,
          tickangle: -45,
          automargin: true,
        },
        yaxis: { automargin: true },
        paper_bgcolor: 'rgba(0,0,0,0)',
        plot_bgcolor: 'rgba(0,0,0,0)',
      }

      const config = { responsive: true, displayModeBar: false }

      this.plotly.react(this.$refs.userDailyHoursHeatmap, [trace], layout, config)
    },

    async loadProjectTasksByDay() {
      if (!this.plotly) return
      this.loadingProjectTasks = true
      this.projectTasksError = null
      this.projectTasksPayload = null

      try {
        const start = moment(this.startDate).format('YYYY-MM-DD')
        const end = moment(this.endDate).format('YYYY-MM-DD')

        const res = await axios.get(this.route('workspace.charts.general.projectTasksByDay'), {
          params: { start_date: start, end_date: end },
        })

        this.projectTasksPayload = res.data || null

        await nextTick()
        this.renderProjectTasksByDayChart()
      } catch (e) {
        this.projectTasksError =
          e?.response?.data?.error ||
          e?.response?.data?.message ||
          'No se pudo cargar la gráfica de tareas por proyecto.'
      } finally {
        this.loadingProjectTasks = false
      }
    },

    renderProjectTasksByDayChart() {
      if (!this.plotly || !this.$refs.projectTasksByDayChart) return
      const payload = this.projectTasksPayload

      const dates = payload?.dates || []
      const series = payload?.series || []

      if (!dates.length || !series.length) {
        this.plotly.purge(this.$refs.projectTasksByDayChart)
        return
      }

      const tickvals = dates
      const ticktext = dates.map(d => moment(d, 'YYYY-MM-DD').format('DD MMM'))

      const traces = series.map(s => ({
        type: 'bar',
        name: s.project,
        x: dates,
        y: (s.y || []).map(v => Number(v || 0)),
        hovertemplate: 'Proyecto: %{fullData.name}<br>Fecha: %{x}<br>Tareas: %{y}<extra></extra>',
      }))

      const height = Math.max(520, 180 + (series.length * 10))

      const layout = {
        barmode: 'stack',
        height,
        margin: { l: 60, r: 20, t: 10, b: 90 },
        xaxis: {
          title: 'Fecha',
          tickmode: 'array',
          tickvals,
          ticktext,
          tickangle: -45,
          automargin: true,
        },
        yaxis: { title: 'Tareas', rangemode: 'tozero' },
        legend: { orientation: 'h', y: 1.08, x: 0, xanchor: 'left' },
        paper_bgcolor: 'rgba(0,0,0,0)',
        plot_bgcolor: 'rgba(0,0,0,0)',
      }

      const config = { responsive: true, displayModeBar: false }

      this.plotly.react(this.$refs.projectTasksByDayChart, traces, layout, config)
    },
  },
}
</script>