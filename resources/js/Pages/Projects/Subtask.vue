<template>
  <!-- Overlay -->
  <div
    v-if="modelValue"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    @click.self="close"
  >
    <!-- Modal -->
    <div
      class="bg-white w-full max-w-md rounded-2xl shadow-2xl p-6 animate-fadeIn"
    >
      <!-- Header -->
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-gray-800">
          Crear Nueva Tarea
        </h2>
        <button
          @click="close"
          class="text-gray-400 hover:text-gray-600 text-xl"
        >
          ✕
        </button>
      </div>

      <!-- Form -->
      <form @submit.prevent="saveSubTask" class="space-y-4">

        <!-- Nombre -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Nombre de la tarea
          </label>
          <input
            v-model="form.title"
            type="text"
            class="w-full rounded-xl border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-300 focus:border-blue-500 outline-none"
            required
          />
        </div>

        <!-- Carril -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Carril
          </label>
          <select
            v-model="form.list_id"
            class="w-full rounded-xl border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-300 focus:border-blue-500 outline-none"
            required
          >
            <option disabled value="">Selecciona un carril</option>
            <option v-for="list in boardList" :key="list.id" :value="list.id">
              {{ list.title }}
            </option>
          </select>
        </div>

        <!-- Columna -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Columna
          </label>
          <select
            v-model="form.sublist_id"
            class="w-full rounded-xl border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-300 focus:border-blue-500 outline-none"
            required
          >
            <option disabled value="">Selecciona una columna</option>
            <option v-for="sublist in boardSublist" :key="sublist.id" :value="sublist.id">
              {{ sublist.title }}
            </option>
          </select>
        </div>

        <loading-button :loading="loadingBtnSave"
        type="submit"
        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-xl transition">
        Crear Tarea</loading-button>
      </form>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import LoadingButton from '@/Shared/LoadingButton'

export default {
  name: "SubTaskModal",
  components: {
    LoadingButton
  },
  props: {
    modelValue: {
      type: Boolean,
      required: true
    },
    projectId: {
      type: Number,
      required: true
    },
    taskId: {
      type: Number,
      required: true
    }
  },


  data() {
    return {
      boardList: [],
      boardSublist: [],
      form: {
        title: "",
        list_id: 0,
        sublist_id: 0
      },
      loadingBtnSave: false,
    };
  },
  created() {
    this.getBoardListByProject(this.projectId);
  },
  watch: {
    "form.list_id"(newVal) {
      this.getBoardSubListByBoardID(newVal);
    }
  },
  methods: {
    close() {
      this.$emit("update:modelValue", false);
    },

    resetForm() {
      this.form.nombre = "";
      this.form.carril = "";
      this.form.columna = "";
    },
    getBoardListByProject(projectId) {
         //this.loadBoardList = true;
        // this.loadSublist = true;
         axios.get(this.route('boardlist.all', projectId)).then((response) => {
            this.boardList = response.data.data;
         }).finally(() => {
          //  this.idBoard = 0;
          ///  this.loadBoardList = false;
          //  this.loadSublist = false;
         });
      },
      getBoardSubListByBoardID(idBoard) {
        // this.loadSublist = true;
         axios.get(this.route('sublist.getbylistid', idBoard)).then((resp) => {
            const response = resp.data;
            if (!response.error) {
               this.boardSublist = response.data;
            }
         }).finally(() => {
          //  this.loadSublist = false;
         });
      },
      saveSubTask(){
        this.loadingBtnSave = true;
         const request = {
            title: this.form.title,
            parent_id: this.taskId,
            project_id: this.projectId,
            list_id: this.form.list_id,
            sublist_id: this.form.sublist_id
         }
         axios.post(this.route('subtask.new'), request
         ).then(res => {
            const data = res.data
            if(!data.error){
              this.form.title = "";
              this.form.list_id = 0;
              this.form.sublist_id = 0;
              this.$toast.open('Datos guardados correctamente.');
              this.$emit('onSubmit', data.data);
              this.close();
            }

         }).catch(e => {
            this.$toast.open({
              message: 'Error al guardar la tarea',
              type: 'error'
            });
         }).finally(() => {
            this.loadingBtnSave = false
         })

      },
  }
};
</script>

<style>
.animate-fadeIn {
  animation: fadeIn 0.2s ease-in-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}
</style>