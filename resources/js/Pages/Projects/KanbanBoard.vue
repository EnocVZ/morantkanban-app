<template>
   <div class="h-full" :class="{ 'right_menu_enable': show_right_menu }">

      <Head :title="__(title)" />
      <board-view-menu :project="project" @filter-toggle="open_filter = !open_filter"
         @menu-toggle="show_right_menu = !show_right_menu" @fClear="reset()" :filters="filters" view="board" />
      <board-filter :project="project" @board-filter="open_filter = false" :filters="filters" v-if="open_filter"
         @do-filter="doFilter" options="user,due,label" />
      <div class="p-6 min-h-screen">
         <div class="space-y-6 pb-6">
            <!-- Contenedor principal -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden"
               v-if="userRequestList.length > 0">
               <!-- Encabezado -->
               <div
                  class="flex justify-between items-center px-6 py-2 bg-gradient-to-r from-indigo-500 to-purple-500 text-white cursor-pointer">
                  <h2 class="font-semibold text-lg flex items-center gap-2">
                     Solicitudes
                  </h2>
                  <div class="flex items-center gap-3">
                     <span class="text-sm opacity-80">{{ userRequestList.length }} pendientes</span>
                  </div>
               </div>

               <!-- Contenido colapsable -->
               <transition name="collapse">
                  <draggable data-id="null" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 p-6 bg-gray-50"
                     :list="userRequestList" group="tasklist" item-key="id" @end="afterDrop($event)">
                     <template #item="{ element, index }">
                        <div :key="index" :id="element.id" :data-id="element.id" @click="taskDetailsPopup(element.id)"
                           class="bg-white p-4 rounded-xl shadow hover:shadow-md transition-shadow duration-300 border border-gray-100 cursor-move">
                           <div class="flex justify-between items-center mb-2">
                              <span class="text-indigo-600 font-medium text-sm">#{{ element.id }}</span>
                              <button class="text-gray-400 hover:text-indigo-500 transition-colors">
                                 <i class="fas fa-ellipsis-h"></i>
                              </button>
                           </div>

                           <h3 class="text-gray-800 font-semibold text-sm mb-1">
                              {{ element.title }}
                           </h3>
                           <p class="text-gray-500 text-xs">
                              {{ element.decription }}
                           </p>
                        </div>
                     </template>
                  </draggable>
               </transition>
            </div>
         </div>

         <!-- Listado de Sprints -->
         <div class="space-y-5">
            <div v-for="(column, index) in board_lists" :key="column.id"
               class="bg-white rounded-xl shadow border border-gray-200 overflow-hidden">
               <!-- Header del Sprint -->
               <div class="flex justify-between items-center px-4 py-3 bg-gray-100 cursor-pointer">
                  <h2 class="font-semibold text-gray-800 flex items-center gap-2" contenteditable="true"
                     @keypress="saveSprintTitle($event, column.id)" @blur="saveSprintTitle($event, column.id)">
                     {{ column.title }}
                  </h2>
                  <div class="flex items-center gap-3">
                     <span class="text-gray-500 text-sm" @click="toggleSprint(column)">
                        {{ column.collapsed ? '▶' : '▼' }}
                     </span>
                  </div>
               </div>

               <!-- Contenido del Sprint -->
               <transition name="fade">
                  <div v-show="!column.collapsed" class="p-4 bg-gray-50">
                           <draggable :data-id="column.id" :data-colids="column.id" class="dragArea grid grid-cols-1 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 overflow-x-auto"
                                    :list="column.sublist" group="columnlist" item-key="id" @end="afterDropColumn($event)">
                                    <template #item="{element:sublist, index:subcolumnIndex }">
                                       <div :id="sublist.id" :data-id="sublist.id" class="li_column bg-white rounded-lg shadow-sm border border-gray-200 flex flex-col min-w-[260px]">
                                          <!-- Encabezado -->
                                          <div class="flex justify-between items-center px-3 py-2 border-b" :class="{
                                             'border-indigo-400': sublist.color === 'blue',
                                             'border-yellow-400': sublist.color === 'yellow',
                                             'border-rose-400': sublist.color === 'red',
                                             'border-green-400': sublist.color === 'green',
                                          }">
                                             <div class="flex justify-between">
                                                <h2 class="font-semibold truncate" contenteditable="true" :aria-label="sublist.title"
                                                   @keypress="saveSublistTitle($event, sublist.id)"
                                                   @blur="saveSublistTitle($event, sublist.id)">
                                                   {{ sublist.title }}</h2>

                                                <span
                                                   class="inline-flex items-center justify-center px-3 py-1 ml-1 mr-1 text-xs cursor-default font-semibold text-indigo-500 bg-indigo-600 rounded-full bg-opacity-30"
                                                   aria-label="Total de tareas">{{ sublist?.tasklist?.length }}</span>
                                             </div>
                                          </div>

                                          <!-- Lista de tareas -->
                                          <ul class="ml-2 mr-2 space-y-2">
                                             <draggable :data-id="sublist.id" :data-colid="column.id" class="dragArea"
                                                :list="sublist.tasklist" group="tasklist" item-key="id" @end="afterDrop($event)">
                                                <template #item="{ element, indexTsk }">
                                                   <li :key="indexTsk" :id="element.id" :data-id="element.id" :data-column="column.id"
                                                      class="li_box bg-white p-2 rounded shadow text-sm mt-2 mb-2 hover:bg-gray-50  cursor-pointer focus:outline-none focus:border focus:border-black p-2 rounded">
                                                      <!-- Etiquetas -->
                                                      <div v-if="element.task_labels.length" class="mb-2 flex flex-wrap gap-1">
                                                         <button v-for="(la, l_index) in element.task_labels" :key="l_index"
                                                            class="text-xs text-white rounded-full px-6 py-1 font-medium"
                                                            :style="{ backgroundColor: la.label.color }" :aria-label="la.label.name">
                                                            
                                                         </button>
                                                      </div>

                                                      <!-- Título -->
                                                      <div class="flex justify-between items-center handle cursor-move">
                                                         <span class="flex text-sm text-gray-800 mb-2 cursor-pointer"
                                                            @click="taskDetailsPopup(element.id)">{{ element.title }}</span>
                                                         <div class="flex items-center gap-2">

                                                            <dropdown className="rounded" placement="bottom-end">
                                                               <template #default>
                                                                  <div class="flex items-center cursor-pointer group">
                                                                     <icon class="w-5 h-5 drop-down-caret-icon fill-gray-400"
                                                                        name="more" />
                                                                  </div>
                                                               </template>
                                                               <template #dropdown>
                                                                  <div class="shadow-xl bg-white rounded text-sm ">
                                                                     <a class="flex px-6 py-2 items-center hover:bg-indigo-500 hover:text-white hover:fill-white"
                                                                        @click="changeWorkspace(element)">
                                                                        <icon class="w-4 h-4 mr-2" name="user_edit" /> {{ __('Cambiarde espacio de trabajo') }}
                                                                     </a>
                                                                  </div>
                                                               </template>
                                                            </dropdown>
                                                         </div>

                                                      </div>

                                                      <!-- Footer -->
                                                      
                                                      <div class="flex items-center text-gray-500 text-xs gap-3">
                                                         <div v-if="element.created_at_for_humans"
                                                            :class="['flex items-center gap-1 hover:text-gray-700', getDue(element)]">
                                                            <icon name="time" class="w-4 h-4" />
                                                            <span>Creado {{ element.created_at_for_humans }}</span>
                                                         </div>
                                                         <!-- Fecha de entrega -->
                                                         <div v-if="element.due_date" title="Fecha de entrega"
                                                            :class="['flex items-center gap-1 hover:text-gray-700', getDue(element)]">
                                                            <icon name="time" class="w-4 h-4" />
                                                            <span>{{ moment(element.due_date).format('MMM D') }}</span>
                                                         </div>

                                                         <!-- Adjuntos -->
                                                         <div v-if="element.attachments_count" class="flex items-center gap-1">
                                                            <icon name="attachment" class="w-4 h-4" />
                                                            <span>{{ element.attachments_count }}</span>
                                                         </div>

                                                         <!-- Checklist -->
                                                         <div v-if="element.checklists_count" class="flex items-center gap-1"
                                                            :class="{ 'text-green-600': element.checklist_done_count === element.checklists_count }">
                                                            <icon name="checklist" class="w-4 h-4" />
                                                            <span>
                                                               {{ element.checklist_done_count + '/' +
                                                                  element.checklists_count }}
                                                            </span>
                                                         </div>

                                                         <div class="flex items-center gap-1">
                                                            <!-- Asignados -->
                                                            <span v-for="assignee in element.assignees" :key="assignee?.user?.id"
                                                               class="block w-6 h-6 rounded-full ring-2 ring-white overflow-hidden"
                                                               :aria-label="assignee?.user?.name">
                                                               <img v-if="assignee?.user?.photo_path" :src="assignee?.user?.photo_path"
                                                                  :alt="assignee?.user?.name" class="w-full h-full object-cover" />
                                                               <img v-else src="/images/svg/profile.svg"
                                                                  class="w-full h-full object-cover" :alt="assignee?.user?.name" />
                                                            </span>
                                                         </div>
                                                      </div>


                                                   </li>
                                                </template>
                                                <template #footer>
                                                   <div class="pt-2">
                                                      <div v-if="!sublist.new_task_open"
                                                         class="flex items-center gap-2 text-sm text-gray-500 hover:text-indigo-600 cursor-pointer"
                                                         @click="sublist.new_task_open = true">
                                                         <icon name="add" class="w-4 h-4 text-indigo-500" />
                                                         <span>Agregar tarea</span>
                                                      </div>

                                                      <div v-show="sublist.new_task_open" class="mt-2">
                                                         <input autofocus :id="'new_task_input_id_' + sublist.id"
                                                            :ref="'new_task_input_' + sublist.id" v-model="new_task.title" type="text"
                                                            class="w-full px-3 py-2 text-sm border rounded-md shadow-sm border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                                                            placeholder="Título de la nueva tarea" />

                                                         <div class="flex gap-2 mt-2">
                                                            <loading-button :loading="sublist.loader"
                                                               class="inline-flex items-center px-3 py-1.5 text-xs text-white bg-indigo-600 hover:bg-indigo-700 rounded-md"
                                                               @click="newTaskInSublist(sublist)">
                                                               Agregar tarea
                                                            </loading-button>

                                                            <button @click="sublist.new_task_open = false"
                                                               class="inline-flex items-center px-3 py-1.5 text-xs bg-white border border-gray-300 text-gray-700 rounded-md hover:bg-gray-100">
                                                               <icon name="close" class="w-4 h-4" />
                                                            </button>
                                                         </div>
                                                      </div>
                                                   </div>
                                                </template>
                                             </draggable>
                                          </ul>
                                       </div>
                                    </template>
                                    <template #footer>
                                       <div>
                                          <button @click.stop="addSubColumn(column)"
                                             class="text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                                             v-show="!column.showformcolumn">
                                             + Agregar columna
                                          </button>
                                          <div class="mt-2" v-if="column.showformcolumn">
                                             <input autofocus type="text" v-model="formColumn.title"
                                                class="w-full px-3 py-2 text-sm border rounded-md shadow-sm border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                                                placeholder="Título de la nueva columna" />

                                             <div class="flex gap-2 mt-2">
                                                <loading-button :loading="column.loadercolumn"
                                                   class="inline-flex items-center px-3 py-1.5 text-xs text-white bg-indigo-600 hover:bg-indigo-700 rounded-md"
                                                   @click="createNewSubColumn(column)">
                                                   Agregar
                                                </loading-button>

                                                <button @click="column.showformcolumn = false"
                                                   class="inline-flex items-center px-3 py-1.5 text-xs bg-white border border-gray-300 text-gray-700 rounded-md hover:bg-gray-100">
                                                   <icon name="close" class="w-4 h-4" />
                                                </button>
                                             </div>
                                          </div>
                                       </div>
                                    </template>
                           </draggable>
                        
                  </div>
               </transition>
            </div>
         </div>
         <!-- Título -->
         <div class="pt-2">
            <!-- Botón crear nuevo sprint -->
            <button @click="formNewSprint.newSprint = true" v-show="!formNewSprint.newSprint"
               class="px-3 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">
               + Nuevo carril
            </button>
            <div v-show="formNewSprint.newSprint">
               <input autofocus type="text" v-model="formNewSprint.title"
                  class="block text-sm font-medium w-full px-4 py-3 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  placeholder="Introduzca un título para el carril" />
               <div class="pl-1 mt-2 flex">
                  <loading-button :loading="formNewSprint.loader"
                     class="inline-flex items-center border font-medium shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 text-white border-transparent bg-indigo-600 hover:bg-indigo-700  px-2.5 py-1.5 text-xs rounded"
                     @click="saveNewSprint">Guardar</loading-button>

                  <button @click="formNewSprint.newSprint = false"
                     class="inline-flex items-center border font-medium shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 text-gray-700 border-gray-300 bg-white hover:bg-gray-50 focus:ring-indigo-500 px-2.5 py-1 text-xs rounded ltr:ml-1 rtl:mr-1">
                     <icon class="w-4 h-4" name="close" />
                  </button>
               </div>
            </div>
         </div>
      </div>
      <task-details v-if="taskDetailsOpen" :id="taskDetailsId" view="board" :isPopup="td_pop"
         @closeModal="closeDetails()" />
      <right-menu v-if="show_right_menu" :project="project" @menu-toggle="show_right_menu = !show_right_menu"
         @openTask="(id) => taskDetailsPopup(id)" />
      <change-workspace v-if="visible.changeWorkspace" @onClose="onCloseChangeWorkSpace" :taskId="taskId" />
   </div>

</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout'
import Icon from '@/Shared/Icon'
import BoardFilter from "@/Shared/BoardFilter";
import BoardViewMenu from '@/Shared/BoardViewMenu'
import draggable from "vuedraggable";
import axios from 'axios'
import LoadingButton from '@/Shared/LoadingButton'
import TaskDetails from '@/Shared/Modals/TaskDetails'
import RightMenu from "@/Shared/RightMenu";
import Dropdown from '@/Shared/Dropdown'
import throttle from "lodash/throttle";
import pickBy from "lodash/pickBy";
import mapValues from "lodash/mapValues";
import ChangeWorkspace from '@/Shared/Modals/ChangeWorkspace'
import moment from 'moment'

export default {
   name: "KanbanBoard",
   components: {
      Head, Link, BoardFilter, BoardViewMenu, draggable, Icon, LoadingButton, TaskDetails,
      RightMenu,
      Dropdown,
      ChangeWorkspace
   },
   layout: Layout,
   remember: 'form',
   props: {
      auth: Object,
      title: String,
      tasks: Object,
      project: Object,
      list_index: Object,
      filters: Object,
      board_lists: {
         required: false
      },
      task: {
         required: false
      },
      existBasicList: {
         type: Boolean,
         default: false
      },
   },
   data() {
      return {
         form: {
            user: this.filters.user,
            due: this.filters.due,
            label: this.filters.label,
            task: this.filters.task ?? null,
         },
         formColumn: {
            title: '',
            list_id: 0,
         },
         formNewSprint: {
            newSprint: false,
            title: '',
            loader: false,
         },
         draggedTask: null,
         new_task: {},
         taskDetailsOpen: false,
         taskDetailsId: '',
         td_pop: false,
         show_right_menu: false,
         open_filter: false,
         visible: {
            changeWorkspace: false,
         },
         taskId: 0,
         userRequestList: [],
      };
   },
   watch: {
      form: {
         deep: true,
         handler: throttle(function () {
            this.$inertia.get(this.route('projects.view.board', this.project.slug || this.project.id), pickBy(this.form), { preserveState: true })
         }, 150),
      },

   },
   created() {
      this.moment = moment
      let currentUrl = this.$page.url.substr(1)
      const currentUrlArray = currentUrl.split('/');

      if (this.task) {
         this.taskDetailsId = this.task.slug || this.task.id;
         this.taskDetailsOpen = true;
      }
      if (!!this.filters.task) {
         this.taskDetailsPopup(this.filters.task)
      }
      this.getUserRequests();

   },
   methods: {
      toggleSprint(sprint) {
         sprint.collapsed = !sprint.collapsed;
      },
      filteredTasks(subcolumn, status) {
         return subcolumn.tasklist.filter((t) => t.status === status);
      },
      addSubColumn(column) {
         column.showformcolumn = true;
         this.formColumn.list_id = column.id;
      },
      goToLink(link) {
         window.location.href = link;
      },
      reset() {
         this.form = mapValues(this.form, () => null)
      },
      doFilter(form) {
         Object.assign(this.form, form);
      },
      createNewSubColumn(column) {
         const colors = ["blue", "yellow", "red", "green"];
         const size = column.sublist.length;
         const color = colors[size % colors.length];
         column.loadercolumn = true;
         
         const request = { ...this.formColumn, color: color, order: (size + 1)  };
         axios.post(this.route('sublist.new'), request).then((response) => {
            if (!response?.data?.error) {
               const data = response.data.data
               data.tasklist = [];
               column.sublist.push(data);
               column.showformcolumn = false;

            }
         }).catch((error) => {
            console.log(error)
         }).finally(() => {
            column.loadercolumn = false;
         })
      },
      saveNewSprint() {
         this.formNewSprint.loader = true;
         const request = {
            project_id: this.project.id,
            order: this.board_lists.length,
            title: this.formNewSprint.title
         };
         if (this.formNewSprint.title) {
            axios.post(this.route('json.list.add'), request).then((response) => {
               if (response.data) {
                  let listItem = response.data;
                  listItem.collapsed = false;
                  listItem.sublist = [];
                  this.formNewSprint.title = '';
                  this.formNewSprint.newSprint = false;
                  //listItem.tasks = [];
                  this.board_lists.push(listItem)
                  // this.openNewList()
               }
            }).finally(() => {
               this.formNewSprint.loader = false;
            })
         }
      },
      newTaskInSublist(sublist) {

         const request = this.new_task;
         const tasks = sublist.tasklist;
         request.sublist_id = sublist.id;
         request.project_id = this.project.id;
         request.list_id = sublist.list_id;
         sublist.loader = true;

         axios.post(this.route('task.new'), request).then((response) => {
            if (response && response.data) {
               tasks.push(response.data)
               this.new_task.title = '';
               sublist.new_task_open = false;
            }
         }).catch((error) => {
            console.log(error)
         }).finally(() => {
            sublist.loader = false;;
         })
      },
      closeDetails() {
         this.form.task = null;
         this.taskDetailsOpen = false
         // this.getBoardLists();
      },
      taskDetailsPopup(id) {
         this.form.task = id;
         this.td_pop = true;
         this.taskDetailsId = id;
         this.taskDetailsOpen = true;
      },

      updateTask(id, taskObject) {
         axios.post(this.route('task.update', id), taskObject).catch((error) => {
            console.log(error)
         })
      },
      saveOrder(taskObject) {
         axios.post(this.route('task.update.order'), taskObject).catch((error) => {
            console.log(error)
         })
      },

      newSortedItems(e, selector, classItem = 'li_box') {
         const lists = e[selector].getElementsByClassName(classItem);
         const newOrder = [];
         for (let i = 0; i < lists.length; i++) {
            newOrder.push({ id: lists[i].dataset.id, order: i + 1 })
         }
         return newOrder;
      },


      afterDrop(e) {
         const new_list = this.newSortedItems(e, 'to');
         let previous_list = [];
         const resquest = {
            updatedlist_at: new Date(),
            sublist_id: e.to.dataset.id,
            userupdate_list: this.auth.user.id,
            list_id: e.to.dataset.colid
         };
         if (!!e.pullMode) {
            previous_list = this.newSortedItems(e, 'from');
            if (e.to.dataset.id !== 'null') {
               this.updateTask(e.item.dataset.id, resquest)
            }
         }
         const list_items = new_list.concat(previous_list);
         if (e.to.dataset.id !== 'null') {
            this.saveOrder(list_items)
         }
         //this.draggingChild = false;
      },

      saveSublistTitle(e, board_id) {
         if (e.keyCode === 13 || e.type === 'blur') {
            e.preventDefault();
            e.target.blur();
            if (e.target.innerText) {
               const title = e.target.innerText.replace(/[^a-zA-Z0-9 _-]/g, "");
               if (title.length > 0) {
                  this.updateSublistInfo(board_id, { title: title }, false);
               }
            }
         }
      },
      updateSublistInfo(id, request, reloadSublist = true) {
         axios.put(this.route('sublist.update.row', id), request).then((response) => {
            if (!response?.data?.error && reloadSublist) {
               // this.getBoardLists();
            }

         }).catch((error) => {
            console.log(error)
         })
      },

      saveSprintTitle(e, board_id) {
         if (e.keyCode === 13 || e.type === 'blur') {
            e.preventDefault();
            e.target.blur();
            if (e.target.innerText) {
               const title = e.target.innerText.replace(/[^a-zA-Z0-9 _-]/g, "");
               this.changeBoardTitle(board_id, title);
            }
         }
      },
      changeBoardTitle(id, title) {
         axios.post(this.route('board.update', id), { title }).then((response) => {
            //console.log(response)
         }).catch((error) => {
            console.log(error)
         })
      },
      changeWorkspace(element) {
         this.taskId = element.id;
         this.visible.changeWorkspace = true;
      },
      onCloseChangeWorkSpace(success = false) {
         this.visible.changeWorkspace = false;
         if (success) {
            this.$inertia.reload({ preserveState: false });
         }
      },

      getUserRequests() {
         axios.get(this.route('userrequest.byprojectid', this.project.id))
            .then((response) => {
               if (!response?.data?.error) {
                  this.userRequestList = response.data.data;
               }
            }).catch((error) => {
               console.log(error)
            })
      },

      getDue(element) {
         return element.is_done ? 'text-green-600' : moment().isAfter(element.due_date) ? 'over_due' : moment(element.due_date).isBetween(moment(), moment().add(1, 'day')) ? 'due_soon' : '';
      },

      afterDropColumn(e) {
         const new_list = this.newSortedItems(e, 'to', 'li_column');
         const list_items = new_list;
         this.saveOrderColumn(list_items)
      },
       saveOrderColumn(list_items) {
         axios.put(this.route('sublist.update.order'), list_items).catch((error) => {
            console.log(error)
         })
      },
   },
};
</script>
<style scoped>
.collapse-enter-active,
.collapse-leave-active {
   transition: all 0.3s ease;
}

.collapse-enter-from,
.collapse-leave-to {
   opacity: 0;
   transform: scaleY(0.95);
}

.fade-enter-active,
.fade-leave-active {
   transition: all 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
   opacity: 0;
   transform: translateY(-10px);
}
</style>
