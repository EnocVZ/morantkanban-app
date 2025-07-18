<template>
  <div class="sec-cont">
      <Head :title="__(title)" />
    <div class="min-w-full py-4 align-middle md:px-3 lg:px-4">
        <div class="flex justify-between items-center">
            <div class="flex">
                
                <button class="bg-transparent hover:bg-blue-500 text-blue-700 font-semibold hover:text-white py-2 px-4 border border-blue-500 hover:border-transparent rounded"
                @click="showModal = true">
                Agregar tarea
                </button>
                <button class="bg-transparent hover:bg-blue-500 text-blue-700 font-semibold hover:text-white py-2 px-4 border border-blue-500 hover:border-transparent rounded"
                @click="copyLink">
                Obtener link para formulario
                </button>
            </div>
            <h2 class="text mb-1 px-2 text-[20px] font-medium">Backlog </h2>
            <div class="tiny__time__log__bar">
                <search-input v-model="form.search" class="w-full max-w-md mr-4" @reset="reset" />
            </div>
        </div>
    </div>
      
      <div class="flex flex-col flex-grow-1 flex-shrink-1 h-full">
          <div class="flex flex-col task__table overflow-y-auto h-full">
              <div class="inline-block min-w-full h-full py-4 align-middle md:px-3 lg:px-4">
                
                  <div class="table__view">
                      <table>
                          <thead>
                          <tr>
                            <th  scope="col" class="w-[20px]">ID</th>
                              <th scope="col" class="w-[17%]">
                                  <button class="flex items-center gap-x-3 focus:outline-none">
                                      <span>{{ __('Tareas') }}</span>
                                  </button>
                              </th>

                              <th scope="col">
                                  {{ __('Descripción') }}
                              </th>

                              <th scope="col" class=" w-[17%]">
                                  {{ __('Tipo de tarea') }}
                              </th>

                          </tr>
                          </thead>
                          <tr   v-for="(listItem, listIndex) in filteredTasks" :key="listItem.id" class="list-group-item group">
                                    <td>{{ listItem?.id }}</td>
                                      <td class="px-2 py-2 text-sm font-medium whitespace-nowrap w-[calc(32%-70px)] hover:bg-gray-100">
                                         <h2 class="font-medium t__title text-pretty">{{ listItem?.title }}</h2>
                                      </td>
                                      <td class="px-2 hide_arrow py-2 text-sm font-medium whitespace-nowrap w-[calc(32%-70px)] cursor-pointer hover:bg-gray-100">
                                          <div class="inline t__title text-sm font-normal rounded-full text-emerald-500 gap-x-2 bg-emerald-100/60 dark:bg-gray-800">
                                              {{ listItem?.description }}
                                          </div>
                                      </td>
                                      <td class="px-1 py-1 hide_arrow t_label text-sm whitespace-nowrap w-[17%] cursor-pointer hover:bg-gray-100">
                                          <span>{{ findCategory(listItem.task_category_id)?.title  || ""}}</span>
                                      </td>

                                       <td class="px-2 py-2 text-sm whitespace-nowrap w-[50px] relative">
                                        <div class="inline-block text-left">
                                          <div>
                                            <button
                                              @click="toggleDropdown(listItem.id)"
                                              type="button"
                                              class="inline-flex justify-center w-8 h-8 rounded-full text-gray-500 hover:bg-gray-200 "
                                            >
                                              ⋮
                                            </button>
                                          </div>
                                          <div
                                            v-if="openDropdownId === listItem.id"
                                            class="absolute right-0 z-10 mt-2 w-auto origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black/5 focus:outline-hidden"
                                          >
                                            <div class="py-1" role="menu" aria-orientation="vertical">
                                              <a class="flex block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                               @click="openMoveTask(listItem.id)">
                                                <icon name="clipboard" class="fill-gray-400 h-4 mr-2"/>Asignar
                                              </a>
                                              <a class=" flex block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                               @click="openEditTask(listItem)">
                                                <icon name="edit" class="fill-gray-400 h-4 mr-2"/>Editar
                                              </a>
                                              <a class=" flex block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                              @click="openDelete(listItem.id)">
                                                <icon name="trash" class="fill-gray-400 h-4 mr-2"/>Eliminar
                                              </a>
                                            </div>
                                          </div>
                                        </div>
                                      </td>
                                  </tr>
                          
                          <tbody v-if="!lists.length">
                          <tr>
                              <td class="border-t px-6 py-4 text-center" colspan="7">{{ __('To tasks found') }}!</td>
                          </tr>
                          </tbody>
                      </table>
                        <div class="flex w-full px-3 pb-3">
                            <pagination class="mt-1" :links="tasks.links" />
                        </div>
            
                  </div>
              </div>
          </div>

      </div>

  </div>
  <!-- Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
    >
      <div
        class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6 relative"
      >
        <!-- Botón cerrar -->
        <button
          @click="showModal = false"
          class="absolute top-3 right-3 text-gray-500 hover:text-gray-700"
        >
          ✖
        </button>

        <!-- Título -->
        <div class="mb-6">
          <h1 class="text-2xl font-semibold text-gray-800 mt-2 flex items-center gap-2">
            <span class="text-black text-3xl"></span> Agregar nueva tarea al backlog
          </h1>
        </div>

        <!-- Tu formulario -->
        <div class="space-y-6">
          <!-- Campo resumen -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Titulo *</label>
            <input
              type="text"
              placeholder="Titulo de la solicitud"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              v-model="formNewTask.title"
            />
          </div>

          <!-- Descripción -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Descripción *</label>
            <textarea
              rows="5"
              placeholder="Describa su solicitud"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              v-model="formNewTask.description"
              ></textarea>
          </div>

          <!-- Environment -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo solicitud</label>
            <select
              class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              v-model="formNewTask.task_category_id"
              >
              <option v-for="category in categories" :value="category.id">{{ category.title }}</option>
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
              class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition font-medium"
              @click="saveNewTask($event)"
            >
              Crear
            </button>
            <button
              type="button"
              @click="showModal = false"
              class="px-6 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 transition"
            >
              Cancelar
            </button>
          </div>
        </div>
      </div>
    </div>
    <move-task v-if="moveToList" 
         @onClose="onFinish"
         :taskId="taskId"
         :workspaceId="workspace.id"/>
          <!-- Modal confirm-->
    <div v-if="openConfirmDialog" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
      <div class="bg-white rounded-lg shadow-lg w-96 p-6">
        <h2 class="text-lg font-semibold mb-4">Confirma que desea realizar la acción</h2>
        <p class="text-sm text-gray-700 mb-6">Esta acción no se puede deshacer.</p>

        <div class="flex justify-end gap-2">
          <loading-button :loading="lodadingDelete" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700"
            @click="confirmDelete">Sí, eliminar</loading-button>
          <button @click="openConfirmDialog = false"
            class="bg-gray-300 text-black px-4 py-2 rounded hover:bg-gray-400">Cancelar</button>
        </div>
      </div>
    </div>
        
</template>

<script>
import {Head, Link} from '@inertiajs/vue3'
import Layout from '@/Shared/Layout'
import Icon from '@/Shared/Icon'
import BoardViewMenu from '@/Shared/BoardViewMenu'
import Datepicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css'
import draggable from 'vuedraggable'
import moment from 'moment'
import throttle from "lodash/throttle";
import pickBy from "lodash/pickBy";
import axios from 'axios'
import SearchInput from '@/Shared/SearchInput'
import Pagination from '@/Shared/Pagination'
import mapValues from "lodash/mapValues";
import MoveTask from '@/Pages/Workspaces/MoveTask';


export default {
  metaInfo: { title: 'Dashboard' },
    components: { Head, Icon, Link, draggable, Datepicker, BoardViewMenu, SearchInput, Pagination, MoveTask },
  layout: Layout,
    props: {
        auth: Object,
        title: String,
        tasks: Object,
        filters: Object,
        workspace: Object,
        list_index: Object,
        board_lists: Object,
    },
    remember: 'form',
    data() {
        return {
            errors: [],
            loading: false,
            taskDetailsOpen: false,
            taskId: '',
            form: {
                search: '',
            },
            search: '',
            filteredTasks: [],
            showModal: false,
            moveToList: false,
            formNewTask: {
                title: '',
                description: '',
                task_category_id: 2, // Default to 'Ayuda'
            },
            categories: [],
            openDropdownId: 0,
            openConfirmDialog: false,
            lodadingDelete: false,
        }
    },
    watch: {
        form: {
            deep: true,
            handler: throttle(function() {
                this.$inertia.get(this.route('workspace.backlog', this.workspace.slug || this.workspace.id), pickBy(this.form), { preserveState: true })
            }, 150),
        },
    },
    computed: {
        isModalVisible() {
            return this.taskDetailsOpen;
        },
        lists(){
            //return items;
            return this.tasks.data
        }
    },
    created() {
        this.moment = moment
        this.getCategory();
        this.filteredTasks = this.tasks.data
       
    },
    methods: {
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
       reset() {
            this.form = mapValues(this.form, () => null)
        },
        copyLink() {
        const link = window.location.origin; // obtiene la URL actual
        navigator.clipboard.writeText(link+ '/w/request/link')
            .then(() => {
            alert('✅ Link copiado al portapapeles');
            })
            .catch(err => {
            console.error('❌ Error al copiar: ', err);
            });
        },
        openMoveTask(taskId) {
            this.taskId = taskId;
            this.moveToList = true;
            //this.openDropdownId = 0;
        },
        onFinish(success = false) {
            this.moveToList = false;
            if (success) {
                  this.$inertia.reload({ preserveState: true });
            }
        },
        getCategory() {
            axios.get(this.route('category.list', this.workspace.id)).then((response) => {
              if(response?.data?.error == false){
                this.categories = response.data.data;
              }
              console.log(response.data);
              //  this.workspaces = response.data;
            });
        },

        findCategory(id) {
            return this.categories.find(category => category.id === id);
        },
        toggleDropdown(id) {
          this.openDropdownId = this.openDropdownId === id ? null : id;
        },
        confirmDelete() {
          this.lodadingDelete = true
          axios.post(this.route('task.delete', this.taskId)).then(() => {
            this.filteredTasks = this.filteredTasks.filter(task => task.id !== this.taskId);
            this.openConfirmDialog = false;
          }).finally(() => {
            this.lodadingDelete = false
          });
        },
        openDelete(taskId) {
            this.taskId = taskId;
            this.openConfirmDialog = true;
        },
        openEditTask(task){
          console.log(task)
          this.formNewTask = {
            title: task.title,
            description: task.description,
            task_category_id: task.task_category_id || 2, // Default to 'Ayuda'
          };
          this.showModal = true;
        }
    },
}
</script>
