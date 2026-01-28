<template>
  <transition name="fade">
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center">
      
      <!-- Overlay -->
      <div
        class="absolute inset-0 bg-black/40 backdrop-blur-sm"
        @click="close"
      ></div>

      <!-- Modal -->
      <div class="relative w-full max-w-md bg-white rounded-xl shadow-xl p-6 z-10">
        
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Automatizar tiempo de vida de las tareas') }}
          </h2>
          <button
            @click="close"
            class="text-gray-400 hover:text-gray-600 transition"
          >
            ✕
          </button>
        </div>
        <div class="space-y-4" v-if="loaderContent">
          <div role="status" class="max-w-sm animate-pulse">
              <div class="h-2.5 bg-gray-200 rounded-full dark:bg-gray-700 mb-4"></div>
          </div>
          <div class="flex justify-end gap-3 pt-4">
              <div role="status" class="max-w-sm animate-pulse">
                  <div class="h-2.5 bg-gray-200 rounded-full dark:bg-gray-700 w-48 mb-4"></div>
              </div>
          </div>
        </div>
        <!-- Body -->
        <form @submit.prevent="submitForm" class="space-y-4" v-else>
          <div class="flex items-end justify-between">
            <div class="flex-grow"> <label class="block text-sm font-medium text-gray-600 mb-1">
                {{ __('Limite en días') }}
              </label>
              <input
                type="number"
                v-model="days_limit"
                min="1"
                required
                class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                placeholder="Ej: 7"
              />
            </div>
             <loading-button type="button" :loading="loaderDelete"
              class="hover:bg-gray-100 px-2 py-2 rounded-lg text-red-500 text-sm transition"
              @click="deleteLimit" v-show="days_limit > 0 && findRow">{{ __('Quitar') }}</loading-button>
          </div>

          <!-- Footer -->
          <div class="flex justify-end gap-3 pt-4">
            <button
              type="button"
              @click="close"
              class="px-4 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-100 transition"
            >
              {{ __('Cancel') }}
            </button>
            <loading-button :loading="loaderSave"
            class="px-4 py-2 btn-indigo" type="submit">{{ __('Save') }}</loading-button>
            
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
    };
  },

  watch: {
    showModal(newVal) {
      this.openModal = newVal;
    },
    sublist(newVal) {
      if (newVal.id > 0) {
        this.getLimitBySublist(newVal.id);
      }
    },
  },
  methods: {
    getLimitBySublist(id) {
      this.loaderContent = true;
        axios.get(this.route('timelife.by.subcolumn', id))
        .then((response)=>{
          const data = response?.data?.data;
          if(data){
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
      // Emit the days limit to the parent component
      console.log(this.sublist)
      this.loaderSave = true;
      axios.post(this.route('timelife.save'), {
        subcolumn_id: this.sublist.id,
        expire_at: this.days_limit,
      }).then((response)=>{
        if(!response.data.error){
          this.$toast.open('Datos guardados correctamente.');
         // this.close();
        }
      }).catch(()=>{
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
      .then((response)=>{
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
