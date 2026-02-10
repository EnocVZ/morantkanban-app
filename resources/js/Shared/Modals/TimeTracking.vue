<template>
  <form @submit.prevent="saveTimeTracking">
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="text-xs text-slate-500">Tiempo empleado en horas</label>
        <input type="number" v-model="form.spent" max="8" min="1" class="mt-1 h-10 w-full rounded-lg border border-slate-300 px-3
                 focus:ring-2 focus:ring-indigo-500 focus:outline-none" required placeholder="" />
      </div>
    </div>

    <!-- Date -->
    <div>
      <label class="text-xs text-slate-500">Fecha de inicio</label>
      <div class="flex gap-2 mt-1">
        <input type="date" v-model="form.date" required class="h-10 w-full rounded-lg border border-slate-300 px-3
                 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
        <input type="time" step="60" v-model="form.time" required class="h-10 rounded-lg border border-slate-300 px-3
                 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
      </div>
    </div>


    <!-- Footer -->
    <div class="flex justify-end gap-3 pt-6">
      <button type="button" class="px-4 py-2 rounded-lg text-slate-600 hover:bg-slate-100" @click="cancelModal">
        Cancelar
      </button>

      <loading-button :loading="loaderSave"
            class="px-4 py-2 btn-indigo" type="submit">{{ __('Save') }}</loading-button>
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
        timerId:0,
        spent: '',
        remaining: '',
        date: '',
        time: '',
        description: '',
      },
      loaderSave: false,
    }
  },
  watch: {
    timer: {
      handler(newTimer) {
        if (newTimer) {
          const dateTime = this.getDateTime(newTimer.started_at);
          this.form.date = dateTime.date;
          this.form.time = dateTime.time;
          this.form.spent = this.convertSecondToHours(newTimer.duration);
          this.form.timerId = newTimer.id;
        }
      },
      immediate: true
    }
  },
  methods: {
    converSecondToHour(seconds) {
      return moment.duration(seconds, 'seconds').format('h[h]')
    },
    convertSecondToHours(seconds) {
      const duration = moment.duration(seconds, 'seconds');
      const hours = Math.floor(duration.asHours());
      const minutes = duration.minutes();
      return hours;
    },
    cancelModal() {
      this.$emit('close');
    },
    formatDateToSave(data) {
      const datetime = moment(
        `${data.date} ${data.time}`,
        'YYYY-MM-DD HH:mm'
      ).format('YYYY-MM-DD HH:mm:ss')
      return datetime;
    },
    getDateTime(date) {
      const result = {
        date: moment(date).format('YYYY-MM-DD'),
        time: moment(date).format('HH:mm'),
      }
      return result
    },
    saveTimeTracking() {
      if(this.form.timerId > 0) {
        this.updateData();
      } else {
        this.saveData();
      }
    },

    saveData(){
      this.loaderSave = true;
      const request = {
        task_id: this.taskId,
        duration: this.form.spent,
        started_at: this.formatDateToSave(this.form)
      }

      axios.post(this.route('task.timer.save'), request).then((response) => {
        this.$toast.success('Seguimiento de tiempo guardado correctamente');
      }).catch((response) => {
        const data = response.response.data;
        if (data.error) {
          if(data?.data?.code === "ERROR_OVERLAPPING_TIMES") {
            this.$toast.error('No puedes guardar en ese rango de tiempo');
          } else {
            this.$toast.error('Error al guardar el seguimiento de tiempo');
          }
        }
      }).finally(() => {
        this.loaderSave = false;
      })
    },

    updateData(){
      this.loaderSave = true;
      const request = {
        duration: this.form.spent,
        started_at: this.formatDateToSave(this.form)
      }

      axios.put(this.route('task.timer.update', this.form.timerId), request).then((response) => {
        this.$toast.success('Seguimiento de tiempo guardado correctamente');
      }).catch((response) => {
        const data = response.response.data;
        if (data.error) {
          if(data?.data?.code === "ERROR_OVERLAPPING_TIMES") {
            this.$toast.error('No puedes guardar en ese rango de tiempo');
          } else {
            this.$toast.error('Error al guardar el seguimiento de tiempo');
          }
        }
      }).finally(() => {
        this.loaderSave = false;
      })
    }
  }


}
</script>