<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Sección azul superior -->
    <div class="bg-blue-600 min-h-[30vh] px-4 flex items-center" :style="{backgroundImage: 'url(/images/gradients/1.svg)'}" >
     
    </div>

    <!-- Formulario con fondo blanco -->
    <div class="max-w-2xl mx-auto -mt-32 bg-white rounded-xl shadow-lg p-8 relative z-10">
      <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-800 mt-2 flex items-center gap-2">
          <span class="text-black text-3xl"></span> Cree una solicitud
        </h1>
      </div>
      <div class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tu correo*</label>
          <input type="email" placeholder="solicitante@correo.com"
            v-model="formNewTask.email"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
        <!-- Campo resumen -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Titulo *</label>
          <input type="text" placeholder="Titulo de la solicitud"
          v-model="formNewTask.title"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>

        <!-- Descripción -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Descripción *</label>
          <textarea rows="5" placeholder="Describa su solicitud"
          v-model="formNewTask.description"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
        </div>

        <!-- Environment -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tipo solicitud</label>
          <select
          v-model="formNewTask.task_category_id"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option>Ayuda</option>
            <option>Bug</option>
            <option>Nueva tarea</option>
          </select>
        </div>
        <div
            class="flex justify-center items-center w-full"
            >
            <label
                for="file-upload"
                class="flex flex-col justify-center items-center w-full h-32 px-4 transition bg-white border-2 border-gray-300 border-dashed rounded-lg cursor-pointer hover:border-blue-500"
            >
                <svg
                class="w-8 h-8 text-gray-500"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M7 16v-4a4 4 0 014-4h3m4 4l-5-5m0 0l-5 5m5-5v12"
                />
                </svg>
                <p class="mt-2 text-sm text-gray-600">
                <span class="font-medium">Haz clic para subir</span> o arrastra y suelta
                </p>
                <input id="file-upload" type="file" class="hidden" />
            </label>
            </div>
       
        <!-- Botones -->
        <div class="flex items-center gap-4">
          <button
            @click="saveNewTask"
            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition font-medium">Enviar</button>
        </div>
      </div>
    </div>

   
  </div>
</template>
<script>
import axios from 'axios'
export default {
  data(){
    return {
      formNewTask:{
         title: '',
         description: '',
         task_category_id: 1,
         email: ''
      }
    }
  },
  methods:{
  saveNewTask(e){
       // e.preventDefault();
            //const tasks = this.lists[listIndex].tasks;
            axios.post(this.route('tasklink.new'), this.formNewTask).then((response) => {
                if(response && response.data){
                   // tasks.push(response.data)
                }
            }).catch((error) => {
                console.log(error)
            })
        },
  }
}
</script>