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

          <div class="w-full lg:w-72" v-if="isAdmin">
            <p class="text-xs text-gray-600 mb-1">{{ __('Usuario') }}</p>

            <select
              v-model="selectedUserId"
              class="w-full px-3 py-2 border rounded-md bg-white hover:bg-gray-50 text-sm"
              :disabled="loading"
            >
              <option :value="null" disabled>-- {{ __('Selecciona usuario') }} --</option>
              <option v-for="u in usersOptions" :key="u.id" :value="u.id">
                {{ u.name }}
              </option>
            </select>
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
          </div>
        </div>

        <p v-if="error" class="mt-3 text-sm text-red-600">
          {{ error }}
        </p>
      </div>

      <!-- Gráfica comportamiento semanal -->
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

                    <th class="text-left text-xs font-semibold text-gray-600 px-4 py-3 w-44">
                      {{ __('Fecha de creación') }}
                    </th>

                    <th class="text-left text-xs font-semibold text-gray-600 px-4 py-3">
                      {{ __('Etiquetas') }}
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
                    <td colspan="5" class="px-4 py-6 text-sm text-gray-500">
                      {{ __('No hay tareas con tiempos en este rango.') }}
                    </td>
                  </tr>

                  <!-- ✅ IMPORTANTE: template v-for para poder renderizar 2 filas hermanas -->
                  <template v-for="row in taskRows" :key="row.id">
                    <!-- Fila principal -->
                    <tr
                      class="border-t cursor-pointer hover:bg-gray-50"
                      @click="toggleTask(row.id)"
                    >
                      <td class="px-4 py-3 text-sm text-gray-900">
                        <div class="font-medium">{{ row.title }}</div>
                        <div class="text-xs text-gray-500">#{{ row.id }}</div>
                      </td>

                      <td class="px-4 py-3 text-sm text-gray-700">
                        {{ formatDateTime(row.createdAt) }}
                      </td>

                      <td class="px-4 py-3">
                        <div class="flex flex-wrap gap-2">
                          <span
                            v-for="(lb, idx) in (row.labels || [])"
                            :key="idx"
                            class="px-2 py-1 rounded text-xs font-semibold border"
                            :style="labelStyle(lb)"
                          >
                            {{ lb.name }}
                          </span>

                          <span v-if="!row.labels || row.labels.length === 0" class="text-xs text-gray-400">
                            -
                          </span>
                        </div>
                      </td>

                      <td class="px-4 py-3 text-sm text-gray-900">
                        <div class="flex items-center justify-between gap-2">
                          <div class="font-semibold">
                            {{ Number(row.hours || 0).toFixed(1) }} hrs
                          </div>

                          <div class="text-xs text-gray-500 flex items-center gap-2">
                            <span v-if="row.times && row.times.length > 0">
                              {{ row.times.length }} sesiones
                            </span>
                            <span v-else>-</span>

                            <span v-if="row.times && row.times.length > 0" class="select-none">
                              {{ isTaskOpen(row.id) ? '▾' : '▸' }}
                            </span>
                          </div>
                        </div>
                      </td>

                      <td class="px-4 py-3">
                        <button
                          type="button"
                          class="text-sm font-semibold text-indigo-600 hover:text-indigo-700"
                          @click.stop="onEditTimes(row)"
                        >
                          {{ __('Editar tiempos') }}
                        </button>
                      </td>
                    </tr>

                    <!-- Fila expandida (subtabla) -->
                    <tr v-if="isTaskOpen(row.id)" :key="`sessions-${row.id}`" class="border-t bg-gray-50">
                      <td colspan="5" class="px-4 py-3">
                        <div class="rounded-md border bg-white overflow-hidden">
                          <div class="px-3 py-2 border-b bg-gray-50 flex items-center justify-between">
                            <div class="text-xs font-semibold text-gray-700">
                              {{ __('Sesiones registradas') }}
                            </div>
                            <div class="text-xs text-gray-500">
                              {{ (row.times || []).length }} {{ __('sesiones') }}
                            </div>
                          </div>

                          <div class="overflow-x-auto">
                            <table class="min-w-full">
                              <thead class="bg-white">
                                <tr class="border-b">
                                  <th class="text-left text-xs font-semibold text-gray-600 px-3 py-2 w-64">
                                    {{ __('Inicio') }}
                                  </th>
                                  <th class="text-left text-xs font-semibold text-gray-600 px-3 py-2 w-64">
                                    {{ __('Fin') }}
                                  </th>
                                  <th class="text-left text-xs font-semibold text-gray-600 px-3 py-2 w-32">
                                    {{ __('Horas') }}
                                  </th>
                                </tr>
                              </thead>

                              <tbody>
                                <tr v-if="!row.times || row.times.length === 0">
                                  <td colspan="3" class="px-3 py-3 text-xs text-gray-400">
                                    -
                                  </td>
                                </tr>

                                <tr
                                  v-for="(t, i) in (row.times || [])"
                                  :key="i"
                                  class="border-b last:border-b-0"
                                >
                                  <td class="px-3 py-2 text-xs text-gray-800">
                                    {{ formatDateTimeFull(t.startedAt) }}
                                  </td>
                                  <td class="px-3 py-2 text-xs text-gray-800">
                                    {{ formatDateTimeFull(t.stoppedAt) }}
                                  </td>
                                  <td class="px-3 py-2 text-xs text-gray-800 font-semibold">
                                    {{ Number(t.hours || 0).toFixed(2) }}h
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                          </div>
                        </div>
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Gráfica por carril por día -->
        <!-- ... -->
      </div>

      <!-- Gráfica semanal por carril -->
      <div class="bg-white rounded-lg shadow-lg p-4 mt-4">
        <div class="flex items-center justify-between mb-3">
          <div>
            <h3 class="text-base font-bold text-gray-900">
              {{ __('% semanal dedicado a cada carril') }}
            </h3>
            <p class="text-xs text-gray-600" v-if="listsWeekReady">
              {{ __('Total:') }} {{ listsWeek.targetHours.toFixed(1) }}h
              ({{ listsWeek.workDays }} {{ __('días hábiles') }} × 8h)
            </p>
          </div>

          <p class="text-xs text-gray-600" v-if="listsWeekReady">
            {{ rangeLabel }}
          </p>
        </div>

        <div ref="listsWeekChart" class="w-full" style="min-height: 420px;"></div>
      </div>

      <!-- Apartado 3: Solicitudes del proyecto -->
      <div class="bg-white rounded-lg shadow-lg p-4 mt-4">
        <div class="flex items-center justify-between mb-3">
          <div>
            <h3 class="text-base font-bold text-gray-900">
              {{ __('Solicitudes con tareas asignadas') }}
            </h3>
            <p class="text-xs text-gray-600">
              {{ __('Listado de solicitudes asociadas a tareas del usuario.') }}
            </p>
          </div>

          <button
            type="button"
            class="px-3 py-2 rounded-md border text-sm hover:bg-gray-50"
            :disabled="loadingRequests"
            @click="loadProjectRequestsTable"
          >
            {{ loadingRequests ? __('Cargando...') : __('Recargar') }}
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left text-xs font-semibold text-gray-600 px-4 py-3">{{ __('Solicitante') }}</th>
                <th class="text-left text-xs font-semibold text-gray-600 px-4 py-3 w-44">{{ __('Fecha') }}</th>
                <th class="text-left text-xs font-semibold text-gray-600 px-4 py-3">{{ __('Tipo') }}</th>
                <th class="text-left text-xs font-semibold text-gray-600 px-4 py-3">{{ __('Tarea') }}</th>
                <th class="text-left text-xs font-semibold text-gray-600 px-4 py-3">{{ __('Asignado') }}</th>
                <th class="text-left text-xs font-semibold text-gray-600 px-4 py-3">{{ __('Lista') }}</th>
                <th class="text-left text-xs font-semibold text-gray-600 px-4 py-3">{{ __('Sublista') }}</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="!loadingRequests && projectRequestsRows.length === 0">
                <td colspan="7" class="px-4 py-6 text-sm text-gray-500">
                  {{ __('No hay solicitudes registradas para este proyecto.') }}
                </td>
              </tr>

              <tr v-for="r in projectRequestsRows" :key="r.requestId" class="border-t hover:bg-gray-50">
                <td class="px-4 py-3 text-sm text-gray-900">{{ r.userRequest }}</td>
                <td class="px-4 py-3 text-sm text-gray-700">
                  {{ formatDateTime(r.dateRequest) }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-700">{{ r.requestType }}</td>
                <td class="px-4 py-3 text-sm text-gray-900">{{ r.taskTitle }}</td>
                <td class="px-4 py-3 text-sm text-gray-700">{{ r.userAssigned || '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700">{{ r.listTitle }}</td>
                <td class="px-4 py-3 text-sm text-gray-700">{{ r.sublistTitle || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ✅ Gráfica: Horarios trabajados -->
      <div class="bg-white rounded-lg shadow-lg p-4 mt-4">
        <div class="flex items-center justify-between mb-3">
          <div>
            <h3 class="text-base font-bold text-gray-900">
              {{ __('Horarios trabajados') }}
            </h3>
            <p class="text-xs text-gray-600">
              {{ __('Actividades realizadas por día.') }}
            </p>
          </div>

          <p class="text-xs text-gray-600" v-if="scheduleReady">
            {{ rangeLabel }}
          </p>
        </div>

        <div ref="scheduleChart" class="w-full" style="min-height: 420px;"></div>
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
import { nextTick } from 'vue'

import Plotly from 'plotly.js-dist-min'

export default {
  components: { Head, BoardViewMenu, Datepicker },
  layout: Layout,

  props: {
    title: String,
    auth: Object,
    project: Object,
  },

  data() {
    const start = moment().startOf('isoWeek')
    const end = moment().endOf('isoWeek')

    return {
      startDate: start.toDate(),
      endDate: end.toDate(),

      loading: false,
      error: null,

      plotly: Plotly,

      kpiReady: false,
      avgHours: 0,
      targetHours: 8,

      selectedUserId: null,

      taskRows: [],

      chartData: {
        dates: [],
        hours: [],
      },

      projectRequestsRows: [],
      loadingRequests: false,

      usersOptions: [],
      peopleRows: [],
      peopleHoursRows: [],

      listsPctReady: false,
      listsPct: {
        dates: [],
        lists: [],
        series: {},
        targetHours: 8,
      },

      listsWeekReady: false,
      listsWeek: {
        labels: [],
        percent: [],
        hours: [],
        targetHours: 0,
        workDays: 0,
      },

      scheduleReady: false,
      scheduleRows: [],

      // ✅ acordeón
      openTaskIds: [],
    }
  },

  computed: {
    rangeLabel() {
      const s = moment(this.startDate).format('YYYY-MM-DD')
      const e = moment(this.endDate).format('YYYY-MM-DD')
      return `${s} → ${e}`
    },

    isAdmin() {
      // ✅ Ajusta según cómo venga tu role desde backend
      // Posibles: auth.user.role, auth.user.role.name, auth.user.role_title, etc.
      const r =
        this.auth?.user?.role?.name ||
        this.auth?.user?.role ||
        this.auth?.user?.role_name ||
        this.auth?.user?.roleTitle ||
        ''

      return String(r).toLowerCase() === 'admin'
    },

  },

  async mounted() {
    // ✅ Si NO es admin: usamos el usuario logueado
    if (!this.isAdmin) {
      this.selectedUserId = 45
      //this.selectedUserId = this.auth?.user?.id ?? null
    } else {
      // ✅ Si es admin: carga opciones y selecciona default (primero o el mismo logueado)
      await this.loadUsersOptions()

      const me = this.auth?.user?.id ?? null
      const exists = this.usersOptions.some(u => u.id === me)
      this.selectedUserId = exists ? me : (this.usersOptions[0]?.id ?? null)
    }
    await this.generate()
  },

  methods: {
    formatDate(d) {
      return d ? moment(d).format('YYYY-MM-DD') : 'YYYY-MM-DD'
    },

    formatTime(value) {
      return value ? moment(value).format('HH:mm') : '-'
    },

    formatDateTime(value) {
      return value ? moment(value).format('YYYY-MM-DD HH:mm') : '-'
    },

    formatDateTimeFull(value) {
      return value ? moment(value).format('YYYY-MM-DD HH:mm') : '-'
    },

    isTaskOpen(taskId) {
      return this.openTaskIds.includes(taskId)
    },

    toggleTask(taskId) {
      if (this.isTaskOpen(taskId)) {
        this.openTaskIds = this.openTaskIds.filter(id => id !== taskId)
        return
      }
      this.openTaskIds = [...this.openTaskIds, taskId]
    },

    async generate() {
      this.error = null
      this.loading = true
      this.kpiReady = false

      try {
        await this.loadIndividual()
        await this.loadProjectRequestsTable()
        await this.loadListsWeek()
        await this.loadWorkSchedule()
      } catch (e) {
        console.error('Error generate():', e)

        const status = e?.response?.status
        const url = e?.config?.url
        const msg =
          e?.response?.data?.error ||
          e?.response?.data?.message ||
          e?.message ||
          'Error desconocido'

        this.error = `Error al generar estadísticas${status ? ` (${status})` : ''}: ${msg}${url ? ` | ${url}` : ''}`
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

      const shapes = [
        {
          type: 'line',
          xref: 'paper',
          x0: 0,
          x1: 1,
          yref: 'y',
          y0: this.targetHours,
          y1: this.targetHours,
          line: { dash: 'dash', width: 2 },
        },
      ]

      const layout = {
        title: 'Comportamiento semanal',
        height: 420,
        margin: { l: 40, r: 20, t: 60, b: 40 },
        xaxis: { title: 'Día', type: 'category' },
        yaxis: { title: 'Horas', rangemode: 'tozero', dtick: 1 },
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
      alert(`Editar tiempos (pendiente). Tarea #${row.id}: ${row.title}`)
    },

    async loadIndividual() {
      if (!this.selectedUserId) {
        this.clearIndividual()
        return
      }

      const start = moment(this.startDate).format('YYYY-MM-DD')
      const end = moment(this.endDate).format('YYYY-MM-DD')

      const res = await axios.get(this.route('charts.individual.hoursByDay'), {
        params: {
          start_date: start,
          end_date: end,
          project_id: this.project.id,
          user_id: this.selectedUserId,
        },
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
          user_id: this.selectedUserId,
        },
      })

      this.taskRows = tableRes.data?.rows || []
      await nextTick()
    },

    async loadProjectRequestsTable() {
      this.loadingRequests = true
      try {
        const res = await axios.get(this.route('charts.project.requestsTable'), {
          params: {
            project_id: this.project.id,
            user_id: this.selectedUserId, // ✅ MISMA variable que todo
          },
        })
        this.projectRequestsRows = res.data?.rows || []
      } finally {
        this.loadingRequests = false
      }
    },

    async loadListsWeek() {
      if (!this.selectedUserId) {
        this.clearListsWeek()
        return
      }

      const start = moment(this.startDate).format('YYYY-MM-DD')
      const end = moment(this.endDate).format('YYYY-MM-DD')

      const res = await axios.get(this.route('charts.project.listsPercentWeek'), {
        params: {
          start_date: start,
          end_date: end,
          project_id: this.project.id,
          user_id: this.selectedUserId,
        },
      })

      const payload = res.data || {}
      const data = payload.data || {}

      this.listsWeek.labels = data.labels || []
      this.listsWeek.percent = data.percent || []
      this.listsWeek.hours = data.hours || []
      this.listsWeek.targetHours = Number(payload.target_hours || 0)
      this.listsWeek.workDays = Number(payload.work_days || 0)

      this.listsWeekReady = true
      await nextTick()
      this.renderListsWeek()
    },

    renderListsWeek() {
      if (!this.plotly || !this.$refs.listsWeekChart) return
      if (!this.listsWeekReady) return

      const y = this.listsWeek.labels
      const x = this.listsWeek.percent

      const hover = y.map((label, idx) => {
        const hrs = this.listsWeek.hours[idx] ?? 0
        return `${label}<br>%: ${Number(x[idx] ?? 0).toFixed(1)}%<br>Horas: ${Number(hrs).toFixed(2)}h`
      })

      const trace = {
        type: 'bar',
        orientation: 'h',
        x,
        y,
        text: x.map(v => `${Number(v).toFixed(1)}%`),
        textposition: 'auto',
        hovertext: hover,
        hoverinfo: 'text',
        name: '%',
      }

      const baseHeight = 160
      const rowHeight = 32
      const h = Math.max(420, baseHeight + (y.length * rowHeight))

      const layout = {
        title: 'Distribución porcentual semanal por carril',
        height: h,
        margin: { l: 220, r: 20, t: 60, b: 40 },
        xaxis: {
          title: 'Porcentaje',
          range: [0, 100],
          ticksuffix: '%',
        },
        yaxis: { automargin: true },
        paper_bgcolor: 'rgba(0,0,0,0)',
        plot_bgcolor: 'rgba(0,0,0,0)',
      }

      const config = { responsive: true, displayModeBar: false }
      this.plotly.react(this.$refs.listsWeekChart, [trace], layout, config)
    },

    clearListsWeek() {
      this.listsWeekReady = false
      this.listsWeek = { labels: [], percent: [], hours: [], targetHours: 0, workDays: 0 }

      if (this.plotly && this.$refs.listsWeekChart) {
        this.plotly.purge(this.$refs.listsWeekChart)
      }
    },

    labelStyle(lb) {
      const hex = (lb?.color || '').trim()

      if (!hex) {
        return {
          backgroundColor: '#e5e7eb',
          color: '#111827'
        }
      }

      const r = parseInt(hex.substr(1, 2), 16)
      const g = parseInt(hex.substr(3, 2), 16)
      const b = parseInt(hex.substr(5, 2), 16)

      const luminance = (0.299 * r + 0.587 * g + 0.114 * b)
      const textColor = luminance > 186 ? '#111827' : '#ffffff'

      return {
        backgroundColor: hex,
        color: textColor
      }
    },

    async loadWorkSchedule() {
      if (!this.selectedUserId) {
        this.clearWorkSchedule()
        return
      }

      const start = moment(this.startDate).format('YYYY-MM-DD')
      const end = moment(this.endDate).format('YYYY-MM-DD')

      const res = await axios.get(this.route('charts.individual.workSchedule'), {
        params: {
          start_date: start,
          end_date: end,
          project_id: this.project.id,
          user_id: this.selectedUserId,
        },
      })

      this.scheduleRows = res.data?.rows || []
      this.scheduleReady = true
      await nextTick()
      this.renderWorkSchedule()
    },

    renderWorkSchedule() {
      if (!this.plotly || !this.$refs.scheduleChart) return

      const rows = this.scheduleRows || []

      const toHourDecimal = (dt) => {
        const m = moment(dt)
        return m.hours() + (m.minutes() / 60)
      }

      const fmtTime = (dt) => moment(dt).format('HH:mm')

      const datesUnique = [...new Set(rows.map(r => r.date))].sort()

      if (datesUnique.length === 0) {
        this.plotly.purge(this.$refs.scheduleChart)
        return
      }

      const dateToIndex = new Map(datesUnique.map((d, i) => [d, i]))

      const nDays = datesUnique.length
      const barWidth = Math.max(8, Math.min(26, Math.round(260 / nDays)))

      const oneColor = '#2563eb'

      const traces = rows.map((r) => {
        const x = dateToIndex.get(r.date)
        const y0 = toHourDecimal(r.startedAt)
        const y1 = toHourDecimal(r.stoppedAt)

        return {
          type: 'scatter',
          mode: 'lines',
          x: [x, x],
          y: [y0, y1],
          line: { width: barWidth, color: oneColor },
          hovertemplate:
            `<b>${(r.taskTitle || '').replaceAll('<','&lt;').replaceAll('>','&gt;')}</b>` +
            `<br>Inicio: ${fmtTime(r.startedAt)}` +
            `<br>Fin: ${fmtTime(r.stoppedAt)}` +
            `<br>Duración: ${Number(r.hours || 0).toFixed(2)} h` +
            `<extra></extra>`,
          showlegend: false,
        }
      })

      const shapes = []
      for (let h = 8; h <= 20; h += 1) {
        shapes.push({
          type: 'line',
          xref: 'paper',
          x0: 0,
          x1: 1,
          yref: 'y',
          y0: h,
          y1: h,
          line: { dash: 'dot', width: 1 },
        })
      }

      const layout = {
        title: 'Horarios trabajados',
        height: 420,
        margin: { l: 70, r: 20, t: 50, b: 55 },

        xaxis: {
          title: 'Fecha',
          tickmode: 'array',
          tickvals: datesUnique.map((_, i) => i),
          ticktext: datesUnique,
          range: [-0.5, (nDays - 1) + 0.5],
          fixedrange: true,
          zeroline: false,
          showgrid: false,
        },

        yaxis: {
          title: 'Hora',
          range: [8, 20],
          dtick: 1,
          tickvals: Array.from({ length: 13 }, (_, i) => 8 + i),
          ticktext: Array.from({ length: 13 }, (_, i) => `${String(8 + i).padStart(2, '0')}:00`),
          showgrid: false,
          zeroline: false,
          fixedrange: true,
        },

        shapes,
        paper_bgcolor: 'rgba(0,0,0,0)',
        plot_bgcolor: 'rgba(0,0,0,0)',
      }

      const config = { responsive: true, displayModeBar: false }
      this.plotly.react(this.$refs.scheduleChart, traces, layout, config)
    },

    clearWorkSchedule() {
      this.scheduleReady = false
      this.scheduleRows = []

      if (this.plotly && this.$refs.scheduleChart) {
        this.plotly.purge(this.$refs.scheduleChart)
      }
    },

    clearIndividual() {
      this.kpiReady = false
      this.avgHours = 0
      this.targetHours = 8
      this.chartData.dates = []
      this.chartData.hours = []
      this.taskRows = []

      if (this.plotly && this.$refs.hoursByDayChart) {
        this.plotly.purge(this.$refs.hoursByDayChart)
      }
    },

    async loadUsersOptions() {
      const res = await axios.get(this.route('charts.project.usersOptions'), {
        params: { project_id: this.project.id },
      })

      this.usersOptions = res.data?.data || []
    },

    renderTasksByUser() {},
    renderHoursByUser() {},
    async loadTasksByUser() {},
    async loadHoursByUser() {},
  },
}
</script>