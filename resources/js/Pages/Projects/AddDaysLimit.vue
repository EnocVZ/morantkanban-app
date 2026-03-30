<template>
  <transition name="fade">
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center">

      <!-- Overlay -->
      <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="close"></div>

      <!-- Modal -->
      <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl p-6 z-10">

        <!-- Header -->
        <div class="flex items-start justify-between mb-6">
          <div>
            <h2 class="text-lg font-semibold text-gray-800">
              {{ __('Automatizar tiempo de vida de tareas') }}
            </h2>

            <p class="text-sm text-gray-500 mt-1">
              Las tareas se marcarán automáticamente después del tiempo definido.
            </p>
          </div>

          <button @click="close" class="text-gray-400 hover:text-gray-600 transition">
            ✕
          </button>
        </div>

        <!-- Loader -->
        <div class="space-y-4" v-if="loaderContent">
          <div class="h-2 bg-gray-200 rounded animate-pulse"></div>
          <div class="h-2 bg-gray-200 rounded animate-pulse w-2/3"></div>
        </div>

        <!-- Body -->
        <form v-else @submit.prevent="submitForm" class="space-y-6">

          <!-- Days limit -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ __('Archivar tareas') }}
            </label>

            <input type="number" required v-model="days_limit" min="1" placeholder="Ej: 7" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm
focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" />

            <p class="text-xs text-gray-400 mt-1">
              Después de este tiempo se archivan las tareas.
            </p>
          </div>

          <!-- Delete action -->
          <div v-if="days_limit > 0 && findRow" class="flex justify-start">
            <loading-button type="button" :loading="loaderDelete"
              class="hover:bg-gray-100 bg-gray-100 px-2 py-2 rounded-lg text-red-500 text-sm transition" @click="deleteLimit"
              v-show="days_limit > 0 && findRow">{{ __('Eliminar automatización') }}</loading-button>


          </div>

          <!-- Footer -->
          <div class="flex justify-end gap-3 pt-4 border-t">

            <button type="button" @click="close"
              class="px-4 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-100 transition">
              {{ __('Cancelar') }}
            </button>

            <loading-button :loading="loaderSave" class="px-4 py-2 btn-indigo" type="submit">
              {{ __('Guardar') }}
            </loading-button>

          </div>

        </form>

      </div>
    </div>
  </transition>
</template>

<script>
import axios from 'axios'
import LoadingButton from '@/Shared/LoadingButton'
export default {
  components: {
    LoadingButton,
  },
  props: {
    showModal: {
      type: Boolean,
      required: true,
    },
    sublist: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      days_limit: null,
      openModal: false,
      loaderContent: false,
      loaderSave: false,
      findRow: false,
      loaderDelete: false,
      enableDone: false,
    };
  },

  watch: {
    showModal(newVal) {
      this.openModal = newVal;
    },
    sublist(newVal) {
      if (newVal.id > 0 && this.openModal) {
        this.getLimitBySublist(newVal.id);
      }
    },
  },
  methods: {
    getLimitBySublist(id) {
      this.loaderContent = true;
      axios.get(this.route('timelife.by.subcolumn', id))
        .then((response) => {
          const data = response?.data?.data;
          if (data) {
            this.days_limit = data.expire_at;
            this.findRow = true;
          }
        })
        .catch((error) => {
          console.log(error)
        }).finally(() => {
          this.loaderContent = false;
        });
    },
    close() {

      this.days_limit = null;
      this.$emit('close');
    },
    submitForm() {
      this.saveLimit();
    },
    saveLimit() {
      // Emit the days limit to the parent component
      this.loaderSave = true;
      axios.post(this.route('timelife.save'), {
        subcolumn_id: this.sublist.id,
        expire_at: this.days_limit,
      }).then((response) => {
        if (!response.data.error) {
          this.$toast.open('Datos guardados correctamente.');
          // this.close();
        }
      }).catch(() => {
        this.$toast.open({
          message: 'Problemas al guardar el limite de días.',
          type: 'error',
        });
      }).finally(() => {
        this.loaderSave = false;
      });
    },
    deleteLimit() {
      this.loaderDelete = true;
      axios.delete(this.route('timelife.delete', this.sublist.id))
        .then((response) => {
          this.days_limit = null;
          this.findRow = false;
          this.$toast.open('Se quito el limite de días correctamente.');
        }).finally(() => {
          this.loaderDelete = false;
        });
    }

  },
};
</script>
