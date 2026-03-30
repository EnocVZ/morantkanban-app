<template>
  <form @submit.prevent="saveTimeTracking">
    
    <!-- Tiempo -->
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="text-xs text-slate-500">Horas</label>
        <input
          type="number"
          v-model.number="form.hours"
          min="0"
          max="24"
          class="mt-1 h-10 w-full rounded-lg border border-slate-300 px-3
          focus:ring-2 focus:ring-indigo-500 focus:outline-none"
        />
      </div>

      <div>
        <label class="text-xs text-slate-500">Minutos</label>
        <input
          type="number"
          v-model.number="form.minutes"
          min="0"
          max="59"
          class="mt-1 h-10 w-full rounded-lg border border-slate-300 px-3
          focus:ring-2 focus:ring-indigo-500 focus:outline-none"
        />
      </div>
    </div>

    <!-- Fecha -->
    <div class="mt-4">
      <label class="text-xs text-slate-500">Fecha de inicio</label>
      <div class="flex gap-2 mt-1">
        <input
          type="date"
          v-model="form.date"
          :max="today"
          required
          class="h-10 w-full rounded-lg border border-slate-300 px-3
          focus:ring-2 focus:ring-indigo-500 focus:outline-none"
        />
        <input
          type="time"
          step="60"
          v-model="form.time"
          required
          class="h-10 rounded-lg border border-slate-300 px-3
          focus:ring-2 focus:ring-indigo-500 focus:outline-none"
        />
      </div>
    </div>

    <!-- Footer -->
    <div class="flex justify-end gap-3 pt-6">
      <button
        type="button"
        class="px-4 py-2 rounded-lg text-slate-600 hover:bg-slate-100"
        @click="cancelModal"
      >
        Cancelar
      </button>

      <loading-button
        :loading="loaderSave"
        class="px-4 py-2 btn-indigo"
        type="submit"
      >
        Guardar
      </loading-button>
    </div>

  </form>
</template>

<script>
import axios from 'axios'
import moment from 'moment'
import LoadingButton from '@/Shared/LoadingButton'

export default {
  components: {
    LoadingButton,
  },

  props: {
    taskId: {
      type: Number,
      required: true
    },
    timer: {
      type: Object,
      required: true
    }
  },

  data() {
    return {
      form: {
        timerId: 0,
        hours: 0,
        minutes: 0,
        date: '',
        time: '',
      },
      loaderSave: false,
      today: ''
    }
  },
  created() {
    this.today = moment().format('YYYY-MM-DD')
  },
  watch: {
    timer: {
      handler(newTimer) {
        if (newTimer) {

          const dateTime = this.getDateTime(newTimer.started_at)
          this.form.date = dateTime.date
          this.form.time = dateTime.time
          this.form.timerId = newTimer.id

          // Convertir segundos → horas y minutos
          const duration = this.convertSecondsToTime(newTimer.duration)
          this.form.hours = duration.hours
          this.form.minutes = duration.minutes
        }
      },
      immediate: true
    }
  },

  methods: {

    convertSecondsToTime(seconds) {
      const duration = moment.duration(seconds, 'seconds')

      return {
        hours: Math.floor(duration.asHours()),
        minutes: duration.minutes()
      }
    },

    getDurationInSeconds() {
      const hours = this.form.hours || 0
      const minutes = this.form.minutes || 0

      return (hours * 3600) + (minutes * 60)
    },

    cancelModal() {
      this.$emit('close')
    },

    formatDateToSave(data) {
      return moment(
        `${data.date} ${data.time}`,
        'YYYY-MM-DD HH:mm'
      ).format('YYYY-MM-DD HH:mm:ss')
    },

    getDateTime(date) {
      return {
        date: moment(date).format('YYYY-MM-DD'),
        time: moment(date).format('HH:mm'),
      }
    },

    saveTimeTracking() {
      if (this.form.timerId > 0) {
        this.updateData()
      } else {
        this.saveData()
      }
    },

    saveData() {
      this.loaderSave = true

      const request = {
        task_id: this.taskId,
        duration: this.getDurationInSeconds(),
        started_at: this.formatDateToSave(this.form)
      }

      axios.post(this.route('task.timer.save'), request)
        .then((response) => {
          const data = response?.data?.data;
          this.$emit('onAddOrUpdateTime', data, true)
          this.$toast.success('Seguimiento de tiempo guardado correctamente')
        })
        .catch((response) => {
          const data = response.response.data

          switch (data?.data?.code) {
            case "ERROR_OVERLAPPING_TIMES":
              this.$toast.error('No puedes guardar en ese rango de tiempo')
              break;
            case "ERROR_ACTIVE_TIMER_EXISTS":
              this.$toast.error('Ya tienes un seguimiento de tiempo activo, deténlo antes de iniciar uno nuevo')
              break;
            default:
              this.$toast.error('Error en el  servidor al guardar el seguimiento de tiempo')
          }
        })
        .finally(() => {
          this.loaderSave = false
        })
    },

    updateData() {
      this.loaderSave = true

      const request = {
        duration: this.getDurationInSeconds(),
        started_at: this.formatDateToSave(this.form)
      }

      axios.put(
        this.route('task.timer.update', this.form.timerId),
        request
      )
        .then((response) => {
          const data = response?.data?.data;
          this.$emit('onAddOrUpdateTime', data, false)
          this.cancelModal()
          this.$toast.success('Seguimiento de tiempo actualizado correctamente')
        })
        .catch((response) => {
          const data = response.response.data

          if (data?.data?.code === "ERROR_OVERLAPPING_TIMES") {
            this.$toast.error('No puedes guardar en ese rango de tiempo')
          } else {
            this.$toast.error('Error al actualizar el seguimiento')
          }
        })
        .finally(() => {
          this.loaderSave = false
        })
    }

  }
}
</script>
