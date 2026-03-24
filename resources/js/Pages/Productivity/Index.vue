<template>
  <div class="h-full">
    <div class="px-4 py-4 h-full overflow-y-auto">
      <!-- Header -->
      <div class="mb-4">
        <h1 class="text-lg font-bold text-gray-900">
          {{ __('Productividad') }}
        </h1>
        <p class="text-sm text-gray-600 mt-1">
          {{ __('Gráficas de productividad.') }}
        </p>
      </div>

      <!-- Filtro principal -->
      <div class="bg-white rounded-lg shadow-lg p-4 mb-4">
        <div class="flex flex-col lg:flex-row gap-4 lg:items-end lg:flex-wrap">
          <!-- Espacio de trabajo -->
          <div class="w-full lg:w-64">
            <p class="text-xs text-gray-600 mb-1">{{ __('Espacio de trabajo') }}</p>
            <select
              v-model="selectedWorkspaceId"
              class="w-full px-3 py-2 border rounded-md bg-white hover:bg-gray-50 text-sm"
              :disabled="loadingFilters || loading"
              @change="onWorkspaceChange"
            >
              <option :value="null" disabled>
                -- {{ __('Selecciona espacio de trabajo') }} --
              </option>
              <option
                v-for="workspace in workspaceOptions"
                :key="workspace.id"
                :value="workspace.id"
              >
                {{ workspace.name }}
              </option>
            </select>
          </div>

          <!-- Proyecto -->
          <div class="w-full lg:w-64">
            <p class="text-xs text-gray-600 mb-1">{{ __('Proyecto') }}</p>
            <select
              v-model="selectedProjectId"
              class="w-full px-3 py-2 border rounded-md bg-white hover:bg-gray-50 text-sm"
              :disabled="loadingFilters || loading || !selectedWorkspaceId"
              @change="onProjectChange"
            >
              <option :value="null" disabled>
                -- {{ __('Selecciona proyecto') }} --
              </option>
              <option
                v-for="project in projectOptions"
                :key="project.id"
                :value="project.id"
              >
                {{ project.title }}
              </option>
            </select>
          </div>

          <!-- Carril: solo visible para project_type = 2 -->
          <div v-if="requiresLane" class="w-full lg:w-64">
            <p class="text-xs text-gray-600 mb-1">{{ __('Carril') }}</p>
            <select
              v-model="selectedLaneId"
              class="w-full px-3 py-2 border rounded-md bg-white hover:bg-gray-50 text-sm"
              :disabled="loadingFilters || loading || !selectedProjectId"
              @change="onLaneChange"
            >
              <option :value="null" disabled>
                -- {{ __('Selecciona carril') }} --
              </option>
              <option
                v-for="lane in laneOptions"
                :key="lane.id"
                :value="lane.id"
              >
                {{ lane.title }}
              </option>
            </select>
          </div>

          <!-- Fecha inicio -->
          <div class="w-full lg:w-56">
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

          <!-- Fecha fin -->
          <div class="w-full lg:w-56">
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

          <!-- Botón generar -->
          <div class="flex gap-3 items-center">
            <button
              type="button"
              class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="loading || !canGenerate"
              @click="generate"
            >
              {{ loading ? __('Generando...') : __('Generar') }}
            </button>

            <p v-if="ready" class="text-xs text-gray-600">
              {{ rangeLabel }}
            </p>
          </div>
        </div>

        <p v-if="error" class="mt-3 text-sm text-red-600">
          {{ error }}
        </p>
      </div>

      <!-- Flujo acumulativo -->
      <div class="bg-white rounded-lg shadow-lg p-4 mb-4">
        <div class="flex items-center justify-between mb-3">
          <div>
            <h3 class="text-base font-bold text-gray-900">
              {{ __('Flujo acumulativo') }}
            </h3>
            <p class="text-xs text-gray-600">
              {{
                requiresLane
                  ? __('Acumulado de tareas por estatus dentro del carril seleccionado.')
                  : __('Acumulado de tareas por carril dentro del proyecto seleccionado.')
              }}
            </p>
          </div>

          <p class="text-xs text-gray-600" v-if="ready">
            {{ rangeLabel }}
          </p>
        </div>

        <div v-if="loadingCumulativeFlow" class="text-xs text-gray-500 mb-2">
          {{ __('Cargando gráfica acumulativa...') }}
        </div>

        <p v-if="cumulativeFlowError" class="text-xs text-red-600 mb-2">
          {{ cumulativeFlowError }}
        </p>

        <div ref="cumulativeFlowChart" class="w-full" style="min-height: 520px;"></div>
      </div>

      <!-- Boxplot -->
      <div class="bg-white rounded-lg shadow-lg p-4 mb-4">
        <div class="flex items-center justify-between mb-3">
          <div>
            <h3 class="text-base font-bold text-gray-900">
              {{ __('Horas por tarea completada') }}
            </h3>
            <p class="text-xs text-gray-600">
              {{ __('Distribución del tiempo que tardó cada usuario en completar tareas.') }}
            </p>
          </div>

          <p class="text-xs text-gray-600" v-if="ready">
            {{ rangeLabel }}
          </p>
        </div>

        <div v-if="loadingBoxplot" class="text-xs text-gray-500 mb-2">
          {{ __('Cargando boxplot...') }}
        </div>

        <p v-if="boxplotError" class="text-xs text-red-600 mb-2">
          {{ boxplotError }}
        </p>

        <div ref="completedTaskBoxplotChart" class="w-full" style="min-height: 520px;"></div>
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
import 'moment/locale/es'
import Plotly from 'plotly.js-dist-min'
import { nextTick } from 'vue'

moment.locale('es')

export default {
  layout: Layout,
  components: { Datepicker },

  props: {
    title: String,
  },

  data() {
    const start = moment().startOf('month')
    const end = moment().endOf('month')

    return {
      plotly: Plotly,

      loading: false,
      loadingFilters: false,
      ready: false,
      error: null,

      startDate: start.toDate(),
      endDate: end.toDate(),

      workspaceOptions: [],
      projectOptions: [],
      laneOptions: [],

      selectedWorkspaceId: null,
      selectedProjectId: null,
      selectedLaneId: null,

      selectedProjectType: null,

      loadingCumulativeFlow: false,
      cumulativeFlowError: null,
      cumulativeFlowPayload: null,

      loadingBoxplot: false,
      boxplotError: null,
      boxplotPayload: null,
    }
  },

  computed: {
    rangeLabel() {
      const s = moment(this.startDate).format('YYYY-MM-DD')
      const e = moment(this.endDate).format('YYYY-MM-DD')
      return `${s} → ${e}`
    },

    requiresLane() {
      return Number(this.selectedProjectType) === 2
    },

    canGenerate() {
      const hasBase =
        !!this.selectedWorkspaceId &&
        !!this.selectedProjectId &&
        !!this.startDate &&
        !!this.endDate

      if (!hasBase) return false
      if (this.requiresLane) return !!this.selectedLaneId

      return true
    },
  },

  async mounted() {
    await this.loadWorkspaces()
  },

  methods: {
    formatDate(d) {
      return d ? moment(d).format('YYYY-MM-DD') : 'YYYY-MM-DD'
    },

    resetProjectAndBelow() {
      this.selectedProjectId = null
      this.selectedProjectType = null
      this.selectedLaneId = null
      this.projectOptions = []
      this.laneOptions = []
      this.resetChartState()
    },

    resetLaneAndBelow() {
      this.selectedLaneId = null
      this.laneOptions = []
      this.resetChartState()
    },

    resetChartState() {
      this.ready = false
      this.error = null

      this.loadingCumulativeFlow = false
      this.cumulativeFlowError = null
      this.cumulativeFlowPayload = null

      this.loadingBoxplot = false
      this.boxplotError = null
      this.boxplotPayload = null

      if (this.plotly && this.$refs.cumulativeFlowChart) {
        this.plotly.purge(this.$refs.cumulativeFlowChart)
      }

      if (this.plotly && this.$refs.completedTaskBoxplotChart) {
        this.plotly.purge(this.$refs.completedTaskBoxplotChart)
      }
    },

    async loadWorkspaces() {
      this.loadingFilters = true
      this.error = null

      try {
        const res = await axios.get(this.route('productivity.filters.workspaces'))
        this.workspaceOptions = res.data?.rows || []
      } catch (e) {
        this.error =
          e?.response?.data?.error ||
          e?.response?.data?.message ||
          'No se pudieron cargar los espacios de trabajo.'
      } finally {
        this.loadingFilters = false
      }
    },

    async onWorkspaceChange() {
      this.resetProjectAndBelow()

      if (!this.selectedWorkspaceId) return

      this.loadingFilters = true
      this.error = null

      try {
        const res = await axios.get(this.route('productivity.filters.projects'), {
          params: {
            workspace_id: this.selectedWorkspaceId,
          },
        })

        this.projectOptions = res.data?.rows || []
      } catch (e) {
        this.error =
          e?.response?.data?.error ||
          e?.response?.data?.message ||
          'No se pudieron cargar los proyectos.'
      } finally {
        this.loadingFilters = false
      }
    },

    async onProjectChange() {
      const selectedProject = this.projectOptions.find(
        p => Number(p.id) === Number(this.selectedProjectId)
      )

      this.selectedProjectType = selectedProject ? Number(selectedProject.project_type) : null

      this.resetLaneAndBelow()

      if (!this.selectedProjectId) return

      if (!this.requiresLane) {
        return
      }

      this.loadingFilters = true
      this.error = null

      try {
        const res = await axios.get(this.route('productivity.filters.lanes'), {
          params: {
            project_id: this.selectedProjectId,
          },
        })

        this.laneOptions = res.data?.rows || []
      } catch (e) {
        this.error =
          e?.response?.data?.error ||
          e?.response?.data?.message ||
          'No se pudieron cargar los carriles.'
      } finally {
        this.loadingFilters = false
      }
    },

    onLaneChange() {
      this.resetChartState()
    },

    async loadCumulativeFlowChart() {
      if (!this.plotly || !this.selectedProjectId) return

      this.loadingCumulativeFlow = true
      this.cumulativeFlowError = null
      this.cumulativeFlowPayload = null

      try {
        const start = moment(this.startDate).format('YYYY-MM-DD')
        const end = moment(this.endDate).format('YYYY-MM-DD')

        let res = null

        if (this.requiresLane) {
          if (!this.selectedLaneId) return

          res = await axios.get(this.route('productivity.charts.cumulativeFlow'), {
            params: {
              lane_id: this.selectedLaneId,
              start_date: start,
              end_date: end,
            },
          })
        } else {
          res = await axios.get(this.route('productivity.charts.cumulativeFlowProject'), {
            params: {
              project_id: this.selectedProjectId,
              start_date: start,
              end_date: end,
            },
          })
        }

        this.cumulativeFlowPayload = res.data || null

        await nextTick()
        this.renderCumulativeFlowChart()
      } catch (e) {
        this.cumulativeFlowError =
          e?.response?.data?.error ||
          e?.response?.data?.message ||
          'No se pudo cargar la gráfica de flujo acumulativo.'
      } finally {
        this.loadingCumulativeFlow = false
      }
    },

    renderCumulativeFlowChart() {
      if (!this.plotly || !this.$refs.cumulativeFlowChart) return

      const payload = this.cumulativeFlowPayload
      const dates = payload?.dates || []
      const series = payload?.series || []

      if (!dates.length || !series.length) {
        this.plotly.purge(this.$refs.cumulativeFlowChart)
        return
      }

      const traces = series.map((s) => ({
        type: 'scatter',
        mode: 'lines',
        name: s.name,
        x: dates,
        y: (s.y || []).map(v => Number(v || 0)),
        stackgroup: 'one',
        line: {
          width: 1.5,
          shape: 'linear',
        },
        hovertemplate:
          'Estatus: %{fullData.name}<br>' +
          'Fecha: %{x}<br>' +
          'Acumulado: %{y}<extra></extra>',
      }))

      const tickvals = dates
      const ticktext = dates.map(d => moment(d, 'YYYY-MM-DD').format('DD MMM'))

      const layout = {
        height: 520,
        margin: { l: 60, r: 20, t: 10, b: 90 },
        xaxis: {
          title: 'Fecha',
          tickmode: 'array',
          tickvals,
          ticktext,
          tickangle: -45,
          automargin: true,
        },
        yaxis: {
          title: 'Tareas acumuladas',
          rangemode: 'tozero',
        },
        legend: {
          orientation: 'v',
          x: 1.02,
          y: 1,
          xanchor: 'left',
          yanchor: 'top',
        },
        paper_bgcolor: 'rgba(0,0,0,0)',
        plot_bgcolor: 'rgba(0,0,0,0)',
        hovermode: 'x unified',
      }

      const config = {
        responsive: true,
        displayModeBar: false,
      }

      this.plotly.react(this.$refs.cumulativeFlowChart, traces, layout, config)
    },

    async loadCompletedTaskBoxplot() {
      if (!this.plotly || !this.canGenerate) return

      this.loadingBoxplot = true
      this.boxplotError = null
      this.boxplotPayload = null

      try {
        const start = moment(this.startDate).format('YYYY-MM-DD')
        const end = moment(this.endDate).format('YYYY-MM-DD')

        const params = {
          start_date: start,
          end_date: end,
          project_id: this.selectedProjectId,
        }

        if (this.requiresLane) {
          params.lane_id = this.selectedLaneId
        }

        if (this.selectedWorkspaceId) {
          params.workspace_id = this.selectedWorkspaceId
        }

        const res = await axios.get(
          this.route('productivity.charts.completedTaskHoursBoxplot'),
          { params }
        )

        this.boxplotPayload = res.data || null

        await nextTick()
        this.renderCompletedTaskBoxplot()
      } catch (e) {
        this.boxplotError =
          e?.response?.data?.error ||
          e?.response?.data?.message ||
          'No se pudo cargar la gráfica boxplot.'
      } finally {
        this.loadingBoxplot = false
      }
    },

    renderCompletedTaskBoxplot() {
      if (!this.plotly || !this.$refs.completedTaskBoxplotChart) return

      const rows = this.boxplotPayload?.rows || []

      if (!rows.length) {
        this.plotly.purge(this.$refs.completedTaskBoxplotChart)
        return
      }

      const traces = rows.map((row) => ({
        type: 'box',
        name: row.user,
        x: (row.values || []).map(v => Number(v || 0)),
        orientation: 'h',
        boxpoints: 'outliers',
        hovertemplate:
          'Usuario: ' + row.user +
          '<br>Horas: %{x:.2f}<extra></extra>',
        marker: { size: 6 },
        line: { width: 1.5 },
      }))

      const height = Math.max(520, 160 + (rows.length * 34))

      const layout = {
        height,
        margin: { l: 220, r: 20, t: 10, b: 50 },
        xaxis: {
          title: 'Horas',
          rangemode: 'tozero',
        },
        yaxis: {
          automargin: true,
          categoryorder: 'array',
          categoryarray: rows.map(r => r.user),
        },
        paper_bgcolor: 'rgba(0,0,0,0)',
        plot_bgcolor: 'rgba(0,0,0,0)',
        showlegend: false,
      }

      const config = {
        responsive: true,
        displayModeBar: false,
      }

      this.plotly.react(this.$refs.completedTaskBoxplotChart, traces, layout, config)
    },

    async generate() {
      this.loading = true
      this.error = null
      this.ready = false

      try {
        if (!this.canGenerate) {
          throw new Error(
            this.requiresLane
              ? 'Debes seleccionar espacio de trabajo, proyecto, carril y rango de fechas.'
              : 'Debes seleccionar espacio de trabajo, proyecto y rango de fechas.'
          )
        }

        const start = moment(this.startDate).format('YYYY-MM-DD')
        const end = moment(this.endDate).format('YYYY-MM-DD')

        if (moment(start).isAfter(end)) {
          throw new Error('El rango de fechas no es válido (inicio > fin).')
        }

        await this.loadCumulativeFlowChart()
        await this.loadCompletedTaskBoxplot()

        this.ready = true
      } catch (e) {
        this.error =
          e?.response?.data?.error ||
          e?.response?.data?.message ||
          e?.message ||
          'No se pudo generar la información.'
      } finally {
        this.loading = false
      }
    },
  },
}
</script>