<template>
  <div  class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
    <toast ref="toast" :type="notificationType" >{{notificationMessage}}</toast>
    <div class="bg-gray-100 w-11/12 max-w-lg p-6 rounded shadow-lg relative">
      <!-- Encabezado: Botón de retroceso y barra de ruta -->
      <div class="flex items-center justify-between mb-4">
        <!-- Botón de retroceso -->
        <button v-if="currentPath.length > 1" @click="goBack"
          class="text-sm text-gray-600 bg-gray-200 px-3 py-2 rounded hover:bg-gray-300 flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
            stroke="currentColor" class="size-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
          </svg>
          Retroceder
        </button>

        <!-- Barra de ruta -->
        <div class="flex-1 mx-4 bg-gray-200 px-3 py-2 rounded text-sm text-gray-700 truncate">
          <span class="font-semibold">Ruta:</span> {{ path }}
        </div>

        <!-- Botón de cerrar -->
        <button class="text-gray-500 hover:text-gray-700" @click="$emit('close')">
          ✖
        </button>
      </div>
      <div v-if="loading" class="grid grid-cols-3 gap-4">
        <!-- Cada carpeta como skeleton -->
        <div v-for="n in 6" :key="n" class="flex flex-col items-center">
          <!-- Skeleton del ícono de la carpeta -->
          <div class="w-16 h-16 bg-gray-200 rounded-lg animate-pulse"></div>
          <!-- Skeleton del texto -->
          <div class="w-12 h-4 bg-gray-200 rounded mt-2 animate-pulse"></div>
        </div>
      </div>
      <!-- Contenedor de carpetas -->
      <div class="grid grid-cols-3 gap-4" v-else>
        <div class="text-center cursor-pointer pt-4" v-if="loaderUpload">
          <div
            class="flex justify-center items-center w-16 h-16 bg-gray-200 border border-gray-300 rounded-lg mx-auto ">
            <!-- Loader -->
            <div class="bg-opacity-80 flex inset-0 justify-center rounded-lg">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="animate-spin size-6">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
              </svg>
            </div>
          </div>
          <span class="mt-2 text-sm">Cargando archivo...</span>
        </div>
        <label class="text-center cursor-pointer pt-4" v-else>
          <div
            class="flex justify-center items-center w-16 h-16 bg-gray-200 border border-gray-300 rounded-lg mx-auto ">

            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
              stroke="currentColor" class="size-6">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
            </svg>
          </div>
          <span class="mt-2 text-sm text-blue-600">Seleccionar archivo</span>
          <input accept="image/png, image/jpeg, image/gif,.doc,.docx,.pdf,.txt,.xlsx,.xlsm,.xlsb" type="file"
            class="hidden" @change="onUploadFile($event)" />
        </label>

        <div v-for="folder in folderList" :key="folder.id" class="text-center cursor-pointer pt-4" :class="{
          'border-2 border-blue-500 bg-blue-50 rounded-md': selectedFolder?.name === folder.name,
        }" @click="selectFolder(folder)" @dblclick="openFolder(folder)">
          <div class="flex justify-center items-center w-16 h-16 bg-gray-200 border border-gray-300 rounded-lg mx-auto">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
              stroke="currentColor" class="size-6">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
            </svg>

          </div>
          <span class="mt-2 text-sm text-gray-700">{{ folder.name }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
  
  <script>
  import axios from "axios";
  import Toast from '@/Shared/Toast';
  
  export default {
    name:"FolderSelection",
    props:["loaderUpload", "exploreFolder", "project"],
    components:{Toast},
    data() {
      return {
        isModalOpen: false,
        folderList:[],
        folders: {
        },
        currentPath: [],
        selectedFolder: {},
        task: null,
        counter: {
          timer: null,
        },
        loading: true,
        notificationMessage: '',
        notificationType: 'success',
      };
    },
    emits: {onUploadFile: null},
    computed: {
        path(){
            let pathName = "";
            this.currentPath.forEach((data, index)=>{
                if(index == 0){
                    pathName = pathName + `${data.name}/`
                }else{
                    pathName = pathName + `${data.name}/`
                }
            })
            return pathName
        }
      
    },
    methods: {
      openModal() {
        this.isModalOpen = true;
      },
      closeModal() {
        this.isModalOpen = false;
        this.selectedFolder = null;
        this.currentPath = [];
      },
      selectFolder(folder) {
        this.selectedFolder = folder;
      },
      openFolder(folder) {
        this.getFolders(folder.id)
        this.currentPath.push(folder);
        this.selectedFolder = folder;
      },
      goBack() {
        if (this.currentPath.length > 1) {
          this.currentPath.pop();
          const lastItem = this.currentPath.at(-1);
          this.getFolders(lastItem.id)
          this.selectedFolder = null;
        }
      },
      async getFolders(folderId) {
        try {
          this.loading = true
          const response = await axios.get(this.route("google.folders", folderId)).finally(()=>{
            this.loading = false
          });
          if(!response.data.error){
            this.folderList = response.data.data
          }else{
            this.notificationType = "error";
            this.notificationMessage = "Problemas al conectarse con drive";
            this.$refs.toast.showToast();
          }
          
        } catch (error) {
          console.error("Error fetching task:", error);
        }
      },
      onUploadFile(e){
        this.$emit('onUploadFile', e, false, this.selectedFolder.id)
       // console.log("evento",e)
      }
    },
    created() {
      const folder = {id: this.project.folderKey, name:"/"}
      this.currentPath.push(folder)
      this.selectedFolder = folder
      const dataInvalid  = [null, "", undefined, " "]
      const id = dataInvalid.includes(this.project.folderKey)?"default": this.project.folderKey
      this.getFolders(id)
    },
  };
  </script>
  
  <style scoped>
  /* Colores similares al diseño */
  .bg-gray-100 {
    background-color: #f7f7f7;
  }
  .bg-gray-200 {
    background-color: #ebebeb;
  }
  .bg-gray-300 {
    background-color: #e0e0e0;
  }
  .text-gray-700 {
    color: #4a4a4a;
  }
  .border-blue-500 {
    border-color: #007bff;
  }
  .bg-blue-50 {
    background-color: #e7f3ff;
  }
  </style>
  