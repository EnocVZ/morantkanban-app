<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Sección azul superior -->
    <div class="bg-blue-600 min-h-[30vh] px-4 flex items-center" :style="{ backgroundImage: 'url(/images/gradients/1.svg)' }"></div>
    <toast ref="toast" :type="notificationType">{{ notificationMessage }}</toast>

    <!-- Formulario con fondo blanco -->
    <div v-if="showForm" class="max-w-2xl mx-auto -mt-32 bg-white rounded-xl shadow-lg p-8 relative z-10">
      <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-800 mt-2 flex items-center gap-2"><span class="text-black text-3xl"></span> Cree una solicitud</h1>
      </div>
      <div class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tu correo*</label>
          <input type="email" placeholder="solicitante@correo.com" v-model="formNewTask.email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" :class="{ 'border-red-500': errorsForm.email }" />
          <p v-if="errorsForm.email" class="text-red-500 text-sm mt-1">{{ errorsForm.email }}</p>
        </div>
        <!-- Campo resumen -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Titulo *</label>
          <input type="text" placeholder="Titulo de la solicitud" v-model="formNewTask.title" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" :class="{ 'border-red-500': errorsForm.title }" />
          <p v-if="errorsForm.title" class="text-red-500 text-sm mt-1">{{ errorsForm.title }}</p>
        </div>

        <!-- Descripción -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Descripción *</label>
          <textarea rows="5" placeholder="Describa su solicitud" v-model="formNewTask.description" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" :class="{ 'border-red-500': errorsForm.description }"></textarea>
          <p v-if="errorsForm.description" class="text-red-500 text-sm mt-1">{{ errorsForm.description }}</p>
        </div>
        
        <!-- proyecto -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Proyecto</label>
            <select
              class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" :class="{'border-red-500': errorsForm.proyecto}"
              v-model="formNewTask.task_project_id"
              >
              <option v-for="project in projects" :value="project.id">{{ project.title }}</option>
            </select>
             <p v-if="errorsForm.proyecto" class="text-red-500 text-sm mt-1">{{ errorsForm.proyecto }}</p>
        </div>
        
        <!-- Environment -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo solicitud</label>
            <select
              class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" :class="{'border-red-500': errorsForm.tipSolicitud}"
              v-model="formNewTask.task_category_id"
              >
              <option v-for="category in categories" :value="category.id">{{ category.title }}</option>
            </select>
             <p v-if="errorsForm.tipSolicitud" class="text-red-500 text-sm mt-1">{{ errorsForm.tipSolicitud }}</p>
        </div>
        <div
          class="flex flex-col justify-center items-center w-full h-40 px-4 transition bg-white border-2 border-gray-300 border-dashed rounded-lg cursor-pointer hover:border-blue-500"
          @dragover.prevent
          @drop.prevent="handleDrop"
          @click="$refs.fileInput.click()"
        >
          <!-- Input oculto -->
          <input 
            id="file-upload" 
            type="file" 
            accept="image/*,application/pdf,.doc,.docx" 
            class="hidden" 
            ref="fileInput"
            @change="handleFile"
          />

            <!-- Vista previa para imagen -->
          <div v-if="previewUrl" class="w-full h-full flex justify-center items-center">
            <img :src="previewUrl" alt="Preview" class="max-h-36 object-contain rounded-lg" />
          </div>

          <!-- Vista previa para otros archivos -->
          <div v-else-if="previewFile" class="w-full h-full flex flex-col justify-center items-center text-gray-600">
            <p class="truncate max-w-xs">{{ previewFile.name }}</p>
          </div>
          <div v-else class="text-center text-gray-600">
            <svg class="w-8 h-8 mx-auto text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16v-4a4 4 0 014-4h3m4 4l-5-5m0 0l-5 5m5-5v12" />
            </svg>
            <p class="mt-2 text-sm"><span class="font-medium">Haz clic</span> o arrastra una imagen aquí</p>
          </div>
        </div>

        <!-- Botones -->
        <div class="flex items-center gap-4">
          <loading-button :loading="loadingSave" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition font-medium" @click="saveNewTask">Enviar</loading-button>
        </div>
      </div>
    </div>
    <div v-else-if="showThanks">
      <div class="max-w-2xl mx-auto -mt-16 bg-white rounded-xl shadow-lg p-8 relative z-10 cuadro-blanco">
        <div class="space-y-2">
          <h1 class="text-2xl font-bold text-card-foreground text-balance text-gray-600">¡{{ thanksMessage }}!</h1>
          <p class="text-muted-foreground text-pretty leading-relaxed">Hemos recibido su solicitud nos pondremos en contacto con usted mediante el  correo proporcionado.</p>
        </div>
        <div class="mt-6 flex justify-center">
          <button class="bg-blue-600 text-white text-lg px-6 py-2 rounded-lg hover:bg-blue-700 transition font-medium hover:shadow" @click="nuevaTarea">Crear nueva solicitud</button>
        </div>
      </div>
      
    </div>
  </div>
</template>
<script>
import axios from 'axios'
import LoadingButton from '@/Shared/LoadingButton'
import Toast from '@/Shared/Toast'

export default {
  components: {
    LoadingButton,
    Toast,
  },
  props: {
    categories: {
      type: Array,
      default: () => [],
    },
    workspace_id: {
      type: Number,
      required: true
    },
    projects:{
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      previewUrl: null,
      previewFile: null,
      loadingSave: false,
      formNewTask:{
         title: '',
         description: '',
         task_category_id: null,
         email: '',
         imagen: null,
         task_project_id:null,

      },
      notificationMessage: '',
      notificationType: '',
      errorsForm: {},
      showForm: true,
      showThanks: false,
      thanksMessage: 'Su solicitud fue creada correctamente',
    }
  },
  watch: {
    'formNewTask.email'(value) {
      if (!value) {
        this.errorsForm.email = 'El correo es obligatorio'
      } else if (!/\S+@\S+\.\S+/.test(value)) {
        this.errorsForm.email = 'El correo no es válido'
      } else {
        this.errorsForm.email = null
      }
    },
    'formNewTask.title'(value) {
      this.errorsForm.title = value ? null : 'El título es obligatorio'
    },
    'formNewTask.description'(value) {
      this.errorsForm.description = value ? null : 'La descripción es obligatoria'
    },
    'formNewTask.task_project_id'(value) {
      this.errorsForm.proyecto = value ? null : 'Debe seleccionar un proyecto'
    },
    'formNewTask.task_category_id'(value) {
      this.errorsForm.tipSolicitud = value ? null : 'Debe seleccionar un tipo de solicitud'
    },
  },
  methods: {
    getParam(name) {
      const urlParams = new URLSearchParams(window.location.search)
      return urlParams.get(name)
    },

    isValidEmail(email) {
      const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      return re.test(email)
    },

    validarForm() {
      this.errorsForm = {}
      if (this.formNewTask.email == '') {
        this.errorsForm.email = 'El correo es obligatorio'
      } else if (!this.isValidEmail(this.formNewTask.email)) {
        this.errorsForm.email = 'El correo no es válido'
      }

      if (!this.formNewTask.title) {
        this.errorsForm.title = 'El título es obligatorio'
      }
      if(!this.formNewTask.description){
        this.errorsForm.description = 'La descripción es obligatoria'
      }
      if(!this.formNewTask.task_project_id){
        this.errorsForm.proyecto = 'Debe seleccionar un proyecto'
      }
      if (!this.formNewTask.task_category_id) {
        this.errorsForm.tipSolicitud = 'Debe seleccionar un tipo de solicitud'
      }
      return Object.keys(this.errorsForm).length === 0
    },

    saveNewTask(e) {
      if (!this.validarForm()) {
        return
      }
      this.loadingSave = true

      const formData = new FormData();
      formData.append('workspace_id', this.workspace_id);
      formData.append('title', this.formNewTask.title);
      formData.append('description', this.formNewTask.description);
      formData.append('email', this.formNewTask.email);
      formData.append('tipo_solicitud', this.formNewTask.task_category_id);
      formData.append('project_id', this.formNewTask.task_project_id);

      if(this.formNewTask.imagen){
          formData.append('file', this.formNewTask.imagen);
      }

      axios.post(this.route('tasklink.new'), formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      }).then((response) => {
          if(!response?.data?.error){
            
            this.formNewTask = {
              title: '',
              description: '',
              task_category_id: null,
              email: '',
              imagen: null,
              task_project_id:null
            }
            this.previewUrl = null;
            this.previewFile = null;
            this.showForm = false
            this.showThanks = true
            this.thanksMessage = 'Su solicitud fue creada correctamente'

            setTimeout(() => {
              this.thanksMessage = 'Hola, envíe una nueva solicitud'
            }, 15000)
          }
        })
        .catch((error) => {
          this.notificationMessage = error?.response?.data?.message || 'Ocurrió un error '
          this.notificationType = 'error'
          this.$refs.toast.showToast()
        })
        .finally(() => {
          this.loadingSave = false
        })
    },

    handleFile(e) {
      const file = e.target.files[0]
      this.setFile(file)
    },
    handleDrop(e) {
      const file = e.dataTransfer.files[0]
      this.setFile(file)
    },

    setFile(file) {
      if (file && file.type.startsWith('image/')) {
        this.formNewTask.imagen = file

        this.previewUrl = URL.createObjectURL(file)
      } else {
        this.previewUrl = null;
        this.formNewTask.imagen = file;
        this.previewFile = file;
      }
    },

    nuevaTarea() {
      this.showForm = true
      this.showThanks = false
      this.formNewTask = {
        title: '',
        description: '',
        task_category_id: null,
        email: '',
        imagen: null,
        task_project_id:null
      }
      this.errorsForm = {}
    },
  },
  
  created(){
    console.log(this.getParam("id"));
    console.log(this.projects,"projects")
    
  }
}
</script>
