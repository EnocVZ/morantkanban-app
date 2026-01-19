<template>
  <div class="h-full">
    <Head :title="__(title)" />
    <board-view-menu :project="project" view="statistics" />

    <div class="px-4 py-4 h-[calc(100%-52px)] overflow-y-auto">
      <!-- Filtro superior -->
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

            <div v-if="kpiReady" class="text-sm text-gray-700">
              <span class="font-semibold">{{ __('Promedio:') }}</span>
              <span class="ml-1">{{ avgHours.toFixed(1) }}h</span>
            </div>
          </div>
        </div>

        <p v-if="error" class="mt-3 text-sm text-red-600">
          {{ error }}
        </p>
      </div>

      <!-- Gráfica -->
      <div class="bg-white rounded-lg shadow-lg p-4">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-base font-bold text-gray-900">
            {{ __('Comportamiento por día') }}
          </h3>
          <p class="text-xs text-gray-600" v-if="kpiReady">
            {{ rangeLabel }}
          </p>
        </div>

        <div ref="hoursByDayChart" class="w-full" style="min-height: 420px;"></div>

        <!-- Tabla -->
        <div class="mt-4">
          <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="px-4 py-3 border-b">
              <h4 class="text-sm font-bold text-gray-900">{{ __('Detalle por tarea') }}</h4>
              <p class="text-xs text-gray-600" v-if="kpiReady">
                {{ __('Muestra las tareas con tiempo registrado en el rango seleccionado.') }}
              </p>
            </div>

            <div class="overflow-x-auto">
              <table class="min-w-full">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="text-left text-xs font-semibold text-gray-600 px-4 py-3">
                      {{ __('Tarea') }}
                    </th>
                    <th class="text-left text-xs font-semibold text-gray-600 px-4 py-3 w-40">
                      {{ __('Tiempos') }}
                    </th>
                    <th class="text-left text-xs font-semibold text-gray-600 px-4 py-3 w-48">
                      {{ __('Acciones') }}
                    </th>
                  </tr>
                </thead>

                <tbody>
                  <tr v-if="!loading && taskRows.length === 0">
                    <td colspan="3" class="px-4 py-6 text-sm text-gray-500">
                      {{ __('No hay tareas con tiempos en este rango.') }}
                    </td>
                  </tr>

                  <tr
                    v-for="row in taskRows"
                    :key="row.id"
                    class="border-t hover:bg-gray-50"
                  >
                    <td class="px-4 py-3 text-sm text-gray-900">
                      <div class="font-medium">{{ row.title }}</div>
                      <div class="text-xs text-gray-500">#{{ row.id }}</div>
                    </td>

                    <td class="px-4 py-3 text-sm text-gray-900">
                      {{ row.hours.toFixed(1) }} hrs
                    </td>

                    <td class="px-4 py-3">
                      <button
                        type="button"
                        class="text-sm font-semibold text-indigo-600 hover:text-indigo-700"
                        @click="onEditTimes(row)"
                      >
                        {{ __('Editar tiempos') }}
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>


      </div>
    </div>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout'
import BoardViewMenu from '@/Shared/BoardViewMenu'
import Datepicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import axios from 'axios'
import moment from 'moment'

export default {
  components: { Head, BoardViewMenu, Datepicker },
  layout: Layout,
  props: { title: String, auth: Object, project: Object },

  data() {
    const start = moment().startOf('week').add(1, 'day') // Lunes (ajuste común)
    const end = moment().endOf('week').add(1, 'day')     // Domingo (ajuste común)

    return {
      startDate: start.toDate(),
      endDate: end.toDate(),
      loading: false,
      error: null,

      plotly: null,
      kpiReady: false,
      avgHours: 0,
      targetHours: 8,
      taskRows: [],
      chartData: {
        dates: [],
        hours: [],
      },
    }
  },

  computed: {
    rangeLabel() {
      const s = moment(this.startDate).format('YYYY-MM-DD')
      const e = moment(this.endDate).format('YYYY-MM-DD')
      return `${s} → ${e}`
    },
  },

  mounted() {
    // Cargar Plotly solo en cliente (evita problemas SSR)
    import('plotly.js-dist-min')
      .then((m) => {
        this.plotly = m.default || m
        this.generate() // carga inicial
      })
      .catch(() => {
        this.error = 'No se pudo cargar Plotly.'
      })
  },

  methods: {
    formatDate(d) {
      return d ? moment(d).format('YYYY-MM-DD') : 'YYYY-MM-DD'
    },

    async generate() {
      this.error = null
      this.loading = true
      this.kpiReady = false

      try {
        const start = moment(this.startDate).format('YYYY-MM-DD')
        const end = moment(this.endDate).format('YYYY-MM-DD')

        const res = await axios.get(this.route('charts.individual.hoursByDay'), {
          params: { start_date: start, end_date: end, project_id: this.project.id, },
        })

        const payload = res.data

        this.avgHours = Number(payload.avg_hours || 0)
        this.targetHours = Number(payload.target_hours || 8)

        this.chartData.dates = payload.data?.dates || []
        this.chartData.hours = payload.data?.hours || []

        this.kpiReady = true
        this.renderHoursByDay()

        const tableRes = await axios.get(this.route('charts.individual.taskHours'), {
          params: {
            start_date: start,
            end_date: end,
            project_id: this.project.id,
          },
        })

        this.taskRows = tableRes.data?.rows || []

      } catch (e) {
        this.error =
          e?.response?.data?.error ||
          e?.response?.data?.message ||
          'Ocurrió un error al generar las estadísticas.'
      } finally {
        this.loading = false
      }
    },

    renderHoursByDay() {
      if (!this.plotly || !this.$refs.hoursByDayChart) return

      const dates = this.chartData.dates
      const hours = this.chartData.hours

      const trace = {
        x: dates,
        y: hours,
        type: 'scatter',
        mode: 'lines+markers',
        name: 'Horas',
        hovertemplate: 'Fecha: %{x}<br>Horas: %{y:.2f}<extra></extra>',
      }

      // Línea de meta
      const shapes = [
        {
          type: 'line',
          xref: 'paper',
          x0: 0,
          x1: 1,
          yref: 'y',
          y0: this.targetHours,
          y1: this.targetHours,
          line: {
            dash: 'dash',
            width: 2,
          },
        },
      ]

      const layout = {
        title: 'Comportamiento semanal',
        height: 420,
        margin: { l: 40, r: 20, t: 60, b: 40 },
        xaxis: {
          title: 'Día',
          type: 'category',
        },
        yaxis: {
          title: 'Horas',
          rangemode: 'tozero',
          dtick: 1,
        },
        shapes,
        annotations: [
          {
            xref: 'paper',
            yref: 'paper',
            x: 0.01,
            y: 1.12,
            text: `Meta ${this.targetHours}h`,
            showarrow: false,
            font: { size: 12 },
          },
          {
            xref: 'paper',
            yref: 'paper',
            x: 0.5,
            y: 0.02,
            text: `<b>${this.avgHours.toFixed(1)}</b>`,
            showarrow: false,
            font: { size: 18 },
            opacity: 0.9,
          },
        ],
        paper_bgcolor: 'rgba(0,0,0,0)',
        plot_bgcolor: 'rgba(0,0,0,0)',
      }

      const config = { responsive: true, displayModeBar: false }

      this.plotly.react(this.$refs.hoursByDayChart, [trace], layout, config)
    },

    onEditTimes(row) {
      // Placeholder (en el futuro abre modal / pantalla de edición)
      alert(`Editar tiempos (pendiente). Tarea #${row.id}: ${row.title}`)
    },
  },
}
</script>
