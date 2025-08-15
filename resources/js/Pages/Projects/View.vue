<template>
    <div class="h-full" :class="{ 'right_menu_enable': show_right_menu }">

        <Head :title="__(title)" />
        <board-view-menu :project="project" @filter-toggle="open_filter = !open_filter"
            @menu-toggle="show_right_menu = !show_right_menu" @fClear="reset()" :filters="filters" view="board" />
        <board-filter :project="project" @board-filter="open_filter = false" :filters="filters" v-if="open_filter"
            @do-filter="doFilter" options="user,due,label" />
        <div class="task_board">

            <div v-if="loading" class="board_width animate-pulse">
                <div role="status" class="l__b">
                    <div class="__img">
                        <icon name="pulse_image" class="__i" />
                    </div>
                    <div class="__t1"></div>
                    <div class="__t2">
                        <div>
                            <div class="__t_l_1" />
                            <div class="__t_l_2"></div>
                        </div>
                        <icon class="__t_l_r" name="user" />
                    </div><span class="sr-only">Loading...</span>
                </div>
                <div role="status" class="l__b">
                    <div class="__img">
                        <icon name="pulse_image" class="__i" />
                    </div>
                    <div class="__t1"></div>
                    <div class="__t2">
                        <div>
                            <div class="__t_l_1" />
                            <div class="__t_l_2"></div>
                        </div>
                        <icon class="__t_l_r" name="user" />
                    </div><span class="sr-only">Loading...</span>
                </div>
                <div role="status" class="l__b">
                    <div class="__img">
                        <icon name="pulse_image" class="__i" />
                    </div>
                    <div class="__t1"></div>
                    <div class="__t2">
                        <div>
                            <div class="__t_l_1" />
                            <div class="__t_l_2"></div>
                        </div>
                        <icon class="__t_l_r" name="user" />
                    </div><span class="sr-only">Loading...</span>
                </div>
                <div role="status" class="l__b">
                    <div class="__img">
                        <icon name="pulse_image" class="__i" />
                    </div>
                    <div class="__t1"></div>
                    <div class="__t2">
                        <div>
                            <div class="__t_l_1" />
                            <div class="__t_l_2"></div>
                        </div>
                        <icon class="__t_l_r" name="user" />
                    </div><span class="sr-only">Loading...</span>
                </div>
                <div role="status" class="l__b">
                    <div class="__img">
                        <icon name="pulse_image" class="__i" />
                    </div>
                    <div class="__t1"></div>
                    <div class="__t2">
                        <div>
                            <div class="__t_l_1" />
                            <div class="__t_l_2"></div>
                        </div>
                        <icon class="__t_l_r" name="user" />
                    </div><span class="sr-only">Loading...</span>
                </div>
            </div>
            <div v-else :class="{ 'v_label': showLabelName }">
                <div class="flex items-center gap-2 p-4 bg-blue-50 border border-blue-200 rounded-lg" v-show="!existingBasicStatus">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>

                    <div class="flex text-gray-700 font-medium">
                        No cuentas con las columnas básicas. 
                        <loading-button :loading="loaderBasicStatus"
                            class="bg-indigo-600 hover:bg-indigo-700 inline-flex items-center ml-2 px-3 py-0.5 rounded-md text-white text-xs"
                            @click="generateBasicStatus">
                            Generar
                        </loading-button>
                    </div>
                </div>

                <!-- Componente Kanban con subcolumnas -->
                <div class="flex space-x-4 overflow-x-auto p-4  min-h-screen">
                    <div v-for="(column, index) in lists" :key="index"
                        class="bg-white rounded-xl shadow-md w-96 flex flex-col">
                        <div class="flex justify-between p-4 border-b">
                            <h2 class="text-lg font-semibold">{{ column.title }}</h2>
                            <button @click="makeListArchive(column.id)" class="text-red-700" v-show="column.is_basic == 0">Eliminar</button>
                            <span class="inline-flex items-center justify-center px-3 py-1 ml-1 mr-1 text-xs cursor-default font-semibold text-indigo-500 bg-indigo-600 rounded-full bg-opacity-30"
                            aria-label="Total de tareas">{{ column?.total_tasks || 0 }}</span>
                        </div>
                        <draggable :data-id="column.id" v-model="column.sublist" :group="{ name: 'sublist', pull: true, put: true }"
                            item-key="id" handle=".handle" class="flex flex-col gap-4 p-4 overflow-y-auto"
                            :disabled="draggingChild" @end="afterDropSublist($event,column)">
                            <template #item="{ element: sub, index:subcolumnIndex }">
                                <div class="li_sublist bg-gray-100 p-3 rounded-md shadow-inner " :id="sub.id" :data-id="sub.id">

                                    <!-- Título con botón de colapsar -->
                                    <div class="flex justify-between items-center handle cursor-move">
                                        <div class="flex w-full text-sm font-semibold"><span class="px-2 py-1 w-full">{{ sub.title }}</span></div>
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="inline-flex items-center justify-center px-2 py-1 ml-1 mr-1 text-xs cursor-default font-semibold text-indigo-500 bg-indigo-600 rounded-full bg-opacity-30"
                                                aria-label="Total de tareas">{{ sub?.tasklist?.length || 0 }}</span>
                                            <button @click="sub.isOpen = !sub.isOpen"
                                                class="text-xs text-indigo-600 hover:underline">
                                                {{ sub.isOpen ? 'Ocultar' : 'Mostrar' }}
                                            </button>
                                        </div>

                                    </div>

                                    <ul class="space-y-2 mt-2" v-if="sub.isOpen">
                                        <draggable :data-id="sub.id" class="dragArea" :list="sub.tasklist"
                                            group="tasklist" item-key="id" @start="draggingChild = true"
                                            @end="afterDrop($event)">
                                            <template #item="{ element, indexTsk }">
                                                <li :key="indexTsk" :id="element.id" :data-id="element.id"
                                                :data-column="column.id"
                                                class="li_box bg-white p-2 rounded shadow text-sm mt-2 mb-2 hover:bg-gray-50 hover:co cursor-pointer focus:outline-none focus:border focus:border-black p-2 rounded">
                                                    <!-- Etiquetas -->
                                                    <div v-if="element.task_labels.length"
                                                        class="mb-2 flex flex-wrap gap-1">
                                                        <button v-for="(la, l_index) in element.task_labels"
                                                            :key="l_index"
                                                            class="text-xs text-white rounded-full px-2 py-0.5 font-medium"
                                                            :style="{ backgroundColor: la.label.color }"
                                                            :aria-label="la.label.name">
                                                            {{ la.label.name }}
                                                        </button>
                                                    </div>

                                                    <!-- Título -->
                                                        <div class="flex justify-between items-center handle cursor-move">
                                                            <span class="flex font-medium text-sm text-gray-800 mb-2 cursor-pointer" @click="taskDetailsPopup(element.id)">{{ element.title }}</span>
                                                            <div class="flex items-center gap-2">
                                                                
                                                                <dropdown className="rounded" placement="bottom-end">
                                                                    <template #default>
                                                                        <div class="flex items-center cursor-pointer group">
                                                                            <icon class="w-5 h-5 drop-down-caret-icon fill-gray-400" name="more" />
                                                                        </div>
                                                                    </template>
                                                                    <template #dropdown>
                                                                        <div class="shadow-xl bg-white rounded text-sm ">
                                                                            <a class="flex px-6 py-2 items-center hover:bg-indigo-500 hover:text-white hover:fill-white" @click="changeWorkspace(element)"><icon class="w-4 h-4 mr-2" name="user_edit" /> {{ __('Cambiar de espacio de trabajo') }}</a>
                                                                        </div>
                                                                    </template>
                                                                </dropdown>
                                                            </div>

                                                        </div>
                                                    
                                                    <!-- Footer -->
                                                    <div
                                                        class="flex flex-wrap items-center text-gray-500 text-xs gap-3">
                                                        <!-- Fecha de entrega -->
                                                        <div v-if="element.due_date"
                                                            :class="['flex items-center gap-1', getDue(element)]">
                                                            <icon name="time" class="w-4 h-4" />
                                                            <span>{{ moment(element.due_date).format('MMM D') }}</span>
                                                        </div>

                                                        <!-- Adjuntos -->
                                                        <div v-if="element.attachments_count"
                                                            class="flex items-center gap-1">
                                                            <icon name="attachment" class="w-4 h-4" />
                                                            <span>{{ element.attachments_count }}</span>
                                                        </div>

                                                        <!-- Checklist -->
                                                        <div v-if="element.checklists_count"
                                                            class="flex items-center gap-1"
                                                            :class="{ 'text-green-600': element.checklist_done_count === element.checklists_count }">
                                                            <icon name="checklist" class="w-4 h-4" />
                                                            <span>
                                                                {{ element.checklist_done_count + '/' +
                                                                element.checklists_count }}
                                                            </span>
                                                        </div>

                                                        <div class="flex items-center gap-1">
                                                            <!-- Asignados -->
                                                            <span v-for="assignee in element.assignees"
                                                                :key="assignee?.user?.id"
                                                                class="block w-6 h-6 rounded-full ring-2 ring-white overflow-hidden"
                                                                :aria-label="assignee?.user?.name">
                                                                <img v-if="assignee?.user?.photo_path"
                                                                    :src="assignee?.user?.photo_path"
                                                                    :alt="assignee?.user?.name"
                                                                    class="w-full h-full object-cover" />
                                                                <img v-else src="/images/svg/profile.svg"
                                                                    class="w-full h-full object-cover"
                                                                    :alt="assignee?.user?.name" />
                                                            </span>
                                                        </div>
                                                    </div>


                                                </li>
                                            </template>

                                            <!-- Footer para añadir nueva tarea -->
                                            <template #footer>
                                                <div class="pt-2">
                                                    <div v-if="!sub.new_task_open"
                                                        class="flex items-center gap-2 text-sm text-gray-500 hover:text-indigo-600 cursor-pointer"
                                                        @click="sub.new_task_open = true">
                                                        <icon name="add" class="w-4 h-4 text-indigo-500" />
                                                        <span>Agregar tarea</span>
                                                    </div>

                                                    <div v-show="sub.new_task_open" class="mt-2">
                                                        <input autofocus :id="'new_task_input_id_' + sub.id"
                                                            :ref="'new_task_input_' + sub.id" v-model="new_task.title"
                                                            type="text"
                                                            class="w-full px-3 py-2 text-sm border rounded-md shadow-sm border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                                                            placeholder="Título de la nueva tarea"
                                                             />

                                                        <div class="flex gap-2 mt-2">
                                                            <loading-button :loading="sub.loader"
                                                                class="inline-flex items-center px-3 py-1.5 text-xs text-white bg-indigo-600 hover:bg-indigo-700 rounded-md"
                                                                @click="newTaskInSublist(index, subcolumnIndex)">
                                                                Agregar tarea
                                                            </loading-button>

                                                            <button @click="sub.new_task_open = false"
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
                        </draggable>
                        <!-- Subcolumnas -->
                        <div class="flex flex-col gap-4 p-4 overflow-y-auto">

                            <div class="bg-gray-100 p-3 rounded-md shadow-inner"
                                v-show="column?.tasks_without_subcategory?.length > 0">
                                <!-- Título con botón de colapsar -->
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-medium">Tareas sin asignar subcolumna</h3>
                                    </div>

                                    
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="inline-flex items-center justify-center px-2 py-1 ml-1 mr-1 text-xs cursor-default font-semibold text-indigo-500 bg-indigo-600 rounded-full bg-opacity-30"
                                            aria-label="Total de tareas">{{ column?.tasks_without_subcategory?.length
                                            || 0}}</span>
                                        <button @click="toggleColumn(index)"
                                            class="text-xs text-indigo-600 hover:underline">
                                            {{ column.isOpen ? 'Ocultar' : 'Mostrar' }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Contenido colapsable sin sub categorias -->
                                <div v-show="column.isOpen">
                                    <ul class="space-y-2 mt-2">
                                        <draggable data-id="null" class="dragArea"
                                            :list="column?.tasks_without_subcategory" group="tasklist" item-key="id"
                                            @start="draggingChild = true"
                                            @end="afterDrop($event)">
                                            <template #item="{ element, indexTsk }">
                                                <li :key="indexTsk" :id="element.id" :data-id="element.id"
                                                    @click="taskDetailsPopup(element.id)"
                                                    class="li_box bg-white p-2 rounded shadow text-sm mt-2 mb-2 hover:bg-gray-50 hover:co cursor-pointer focus:outline-none focus:border focus:border-black p-2 rounded">
                                                    <!-- Etiquetas -->
                                                    <div v-if="element.task_labels.length"
                                                        class="mb-2 flex flex-wrap gap-1">
                                                        <button v-for="(la, l_index) in element.task_labels"
                                                            :key="l_index"
                                                            class="text-xs text-white rounded-full px-2 py-0.5 font-medium"
                                                            :style="{ backgroundColor: la.label.color }"
                                                            :aria-label="la.label.name">
                                                            {{ la.label.name }}
                                                        </button>
                                                    </div>

                                                    <!-- Título -->
                                                    <div class="font-medium text-sm text-gray-800 mb-2">{{ element.title
                                                        }} </div>

                                                    <!-- Footer -->
                                                    <div
                                                        class="flex flex-wrap items-center text-gray-500 text-xs gap-3">
                                                        <!-- Fecha de entrega -->
                                                        <div v-if="element.due_date"
                                                            :class="['flex items-center gap-1', getDue(element)]">
                                                            <icon name="time" class="w-4 h-4" />
                                                            <span>{{ moment(element.due_date).format('MMM D') }}</span>
                                                        </div>

                                                        <!-- Adjuntos -->
                                                        <div v-if="element.attachments_count"
                                                            class="flex items-center gap-1">
                                                            <icon name="attachment" class="w-4 h-4" />
                                                            <span>{{ element.attachments_count }}</span>
                                                        </div>

                                                        <!-- Checklist -->
                                                        <div v-if="element.checklists_count"
                                                            class="flex items-center gap-1"
                                                            :class="{ 'text-green-600': element.checklist_done_count === element.checklists_count }">
                                                            <icon name="checklist" class="w-4 h-4" />
                                                            <span>
                                                                {{ element.checklist_done_count + '/' +
                                                                element.checklists_count }}
                                                            </span>
                                                        </div>

                                                        <div class="flex items-center gap-1">
                                                            <!-- Asignados -->
                                                            <span v-for="assignee in element.assignees"
                                                                :key="assignee?.user?.id"
                                                                class="block w-6 h-6 rounded-full ring-2 ring-white overflow-hidden"
                                                                :aria-label="assignee?.user?.name">
                                                                <img v-if="assignee?.user?.photo_path"
                                                                    :src="assignee?.user?.photo_path"
                                                                    :alt="assignee?.user?.name"
                                                                    class="w-full h-full object-cover" />
                                                                <img v-else src="/images/svg/profile.svg"
                                                                    class="w-full h-full object-cover"
                                                                    :alt="assignee?.user?.name" />
                                                            </span>
                                                        </div>
                                                    </div>


                                                </li>
                                            </template>
                                        </draggable>
                                    </ul>
                                </div>
                            </div>

                            <!-- Botón para agregar subcolumna -->
                            <button v-show="!column.newsubcolumn" @click="addSubcolumn(index)"
                                class="text-sm text-indigo-700 hover:underline">
                                + Agregar subcolumna
                            </button>
                            <div class="mb-2" v-show="column.newsubcolumn">
                                <input autofocus :id="'newColum' + column.id" :ref="'newColum' + column.id" type="text"
                                    v-model="formSublist.title"
                                    class="block text-sm font-medium w-full px-4 py-3 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Introduzca un título para la subcolumna"
                                    />
                                <div class="pl-1 mt-2 flex">
                                    <loading-button :loading="column.loader"
                                        class="inline-flex items-center border font-medium shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 text-white border-transparent bg-green-500 hover:bg-green-600  px-2.5 py-1.5 text-xs rounded"
                                        @click="createNewSubColumn(index)">Guardar</loading-button>

                                    <button @click="column.newsubcolumn = false"
                                        class="inline-flex items-center border font-medium shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 text-gray-700 border-gray-300 bg-white hover:bg-gray-50 focus:ring-indigo-500 px-2.5 py-1 text-xs rounded ltr:ml-1 rtl:mr-1">
                                        <icon class="w-4 h-4" name="close" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Componente Kanban con subcolumnas -->

                <div class="flex-shrink-0 w-6"></div>
            </div>
        </div>
        <task-details v-if="taskDetailsOpen" :id="taskDetailsId" view="board" :isPopup="td_pop"
            @closeModal="closeDetails()" />
        <right-menu v-if="show_right_menu" :project="project" @menu-toggle="show_right_menu = !show_right_menu"
            @openTask="(id) => taskDetailsPopup(id)" />
        <change-workspace v-if="visible.changeWorkspace" @onClose="onCloseChangeWorkSpace" :taskId="taskId" />
<
    </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout'
import Icon from '@/Shared/Icon'
import TaskDetails from '@/Shared/Modals/TaskDetails'
import BoardViewMenu from '@/Shared/BoardViewMenu'
import draggable from 'vuedraggable'
import moment from 'moment'
import BoardFilter from "../../Shared/BoardFilter";
import throttle from "lodash/throttle";
import pickBy from "lodash/pickBy";
import mapValues from "lodash/mapValues";
import RightMenu from "../../Shared/RightMenu";
import axios from 'axios'
import ChangeWorkspace from '@/Shared/Modals/ChangeWorkspace'
import LoadingButton from '@/Shared/LoadingButton'
import Dropdown from '@/Shared/Dropdown'
import { list } from 'postcss'

export default {
    metaInfo: { title: 'Dashboard' },
    components: { RightMenu, BoardFilter, Head, Icon, Link, draggable, TaskDetails, BoardViewMenu, ChangeWorkspace, LoadingButton,Dropdown },
    layout: Layout,
    props: {
        auth: Object,
        title: String,
        tasks: Object,
        project: Object,
        list_index: Object,
        filters: Object,
        proyectLists: {
            required: false
        },
        task: {
            required: false
        },
        existingBasicStatus: {
            type: Boolean,
            default: false
        },
    },
    remember: 'form',
    data() {
        return {
            errors: [],
            loading: false,
            show_right_menu: false,
            new_list_open: false,
            td_pop: false,
            showLabelName: false,
            firstResponse: [],
            lastResponse: [],
            new_task: {},
            new_list: {},
            taskDetailsOpen: false,
            activeTimerString: '',
            months: [],
            counter: { seconds: 0, timer: this.timer },
            drag: false,
            new_task_open: false,
            taskDetailsId: '',
            open_filter: false,
            form: {
                user: this.filters.user,
                due: this.filters.due,
                label: this.filters.label,
                task: this.filters.task ?? null,
            },
            visible: {
                changeWorkspace: false,
            },
            taskId: 0,
            
            formSublist: {
                title: '',
                list_id: 0,
            },
            draggingChild: false,
            loaderBasicStatus: false,
            lists:[]

        }
    },
    computed: {

    },
    watch: {
        form: {
            deep: true,
            handler: throttle(function () {
                this.$inertia.get(this.route('projects.view.board', this.project.slug || this.project.id), pickBy(this.form), { preserveState: true })
            }, 150),
        },
    },
    mounted(){
    },
    created() {
        this.moment = moment
        // for (let i = 0; i < this.list.length; i++) {
        //     this.list[i].tasks = [...this.tasks];
        // }
        // console.log(this.list[1].tasks);

        let currentUrl = this.$page.url.substr(1)
        const currentUrlArray = currentUrl.split('/');

        // if (urls[0] === '') {
        //     return currentUrl === ''
        // }
        // return urls.filter(url => currentUrl.startsWith(url)).length
        if (this.task) {
            this.taskDetailsId = this.task.slug || this.task.id;
            this.taskDetailsOpen = true;
        }
        if (!!this.filters.task) {
            this.taskDetailsPopup(this.filters.task)
        }
        this.lists = this.proyectLists || [];
    },
    methods: {
        getDoneCount(list) {
            return list.tasks.filter((t) => !!t.is_done).length;
        },
        getDue(element) {
            return element.is_done ? 'done' : moment().isAfter(element.due_date) ? 'over_due' : moment(element.due_date).isBetween(moment(), moment().add(1, 'day')) ? 'due_soon' : '';
        },
        openNewTask(listItem) {
            for (let n = 0; n < this.lists.length; n++) {
                if (!!this.lists[n].new_task_open) {
                    this.lists[n].new_task_open = false;
                }
            }
            listItem.new_task_open = true
            this.new_task.title = '';
            this.setFocus(this.$refs['new_task_input_' + listItem.id][0]);
        },
        openNewList() {
            this.new_list.title = '';
            this.new_list_open = true
            this.setFocus(this.$refs['new_list_input_' + this.lists.length]);
        },
        setFocus(ref) {
            setTimeout(function () {
                if (ref) {
                    ref.focus();
                }
            }, 10);
        },
        closeDetails() {
            this.form.task = null;
            this.taskDetailsOpen = false
        },
        reset() {
            this.form = mapValues(this.form, () => null)
        },
        doFilter(form) {
            Object.assign(this.form, form);
        },
        submitNewList(e) {
            e.preventDefault();
            if (this.new_list.title) {
                axios.post(this.route('json.list.add'), { project_id: this.project.id, order: this.lists.length, title: this.new_list.title }).then((response) => {
                    if (response.data) {
                        const listItem = response.data;
                        listItem.tasks = [];
                        this.lists.push(listItem)
                        this.openNewList()
                    }
                })
            } else {
                this.new_list_open = false;
            }
        },

        makeListArchive(id) {
            axios.post(this.route('json.list.archive', id)).then((response) => {
                if (response.data) {
                    this.$inertia.reload({ preserveState: false });
                }
            })
        },
        makeArchive(e, id, tasks, index) {
            e.preventDefault();
            e.stopPropagation();
            this.saveTask(id, { is_archive: 1 });
            tasks.splice(index, 1)
        },
        saveListTitle(e, board_id) {
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
                console.log(response)
            }).catch((error) => {
                console.log(error)
            })
        },
        afterDropSublist(e, column) {
            
             const new_list = this.newSortedItems(e, 'to', 'li_sublist');
            let previous_list = [];
            const resquest = {
                list_id: e.to.dataset.id,
                // userupdate_list: this.auth.user.id
            };

            if (!!e.pullMode) {
                //resquest.list_id = e.to.dataset.id;
                previous_list = this.newSortedItems(e, 'from', 'li_sublist');
                this.saveNewSublist(e.item.dataset.id, resquest)
                
            }
            const list_items = new_list.concat(previous_list);
            if (e.to.dataset.id !== 'null') {
               // this.saveOrder(list_items)
            }
            this.draggingChild = false;
        },
        afterDrop(e) {
            const new_list = this.newSortedItems(e, 'to');
            let previous_list = [];
            const resquest = {
                updatedlist_at: new Date(),
                sublist_id: e.to.dataset.id,
                userupdate_list: this.auth.user.id,
                list_id:e.item.dataset.column
            };

            if (!!e.pullMode) {
                previous_list = this.newSortedItems(e, 'from');
                if (e.to.dataset.id !== 'null') {
                    this.saveTask(e.item.dataset.id, resquest)
                }
            }
            const list_items = new_list.concat(previous_list);
            if (e.to.dataset.id !== 'null') {
                this.saveOrder(list_items)
            }
            this.draggingChild = false;
        },
        newSortedItems(e, selector, classItem = 'li_box') {
            const lists = e[selector].getElementsByClassName(classItem);
            const newOrder = [];
            for (let i = 0; i < lists.length; i++) {
                newOrder.push({ id: lists[i].dataset.id, order: i + 1 })
            }
            return newOrder;
        },
        saveTask(id, taskObject) {
            axios.post(this.route('task.update', id), taskObject).catch((error) => {
                console.log(error)
            })
        },
        saveOrder(taskObject) {
            axios.post(this.route('task.update.order'), taskObject).catch((error) => {
                console.log(error)
            })
        },
        submitNewTask(listItem, listIndex) {
            if (this.new_task.title) {
                let task = {
                    title: this.new_task.title,
                    project_id: this.project.id,
                    list_id: listItem.id,
                    order: listItem.tasks.length + 1,
                    sublist_id: this.tabOptions[listItem.id] || 0,
                };
                this.saveNewTask(task, listIndex);
                this.openNewTask(listItem)
            } else {
                listItem.new_task_open = false
            }
        },
        //deprecated
        saveNewTask(taskObject, listIndex) {
            const tasks = this.lists[listIndex].tasks;
            axios.post(this.route('task.new'), taskObject).then((response) => {
                if (response && response.data) {
                    tasks.push(response.data)
                }
            }).catch((error) => {
                console.log(error)
            })
        },
        taskDetailsPopup(id) {
            this.form.task = id;
            this.td_pop = true;
            this.taskDetailsId = id;
            this.taskDetailsOpen = true;
        },
        goToLink(link) {
            window.location.href = link;
        },
        add: function () {
            this.list.push({ name: "Juan" });
        },
        replace: function () {
            this.list = [{ name: "Edgard" }];
        },
        clone: function (el) {
            return {
                name: el.name + " cloned"
            };
        },
        log: function (evt) {
            window.console.log(evt);
        },
        changeWorkspace( element) {
            this.taskId = element.id;
            this.visible.changeWorkspace = true;
        },
        onCloseChangeWorkSpace(success = false) {
            this.visible.changeWorkspace = false;
            if (success) {
                this.$inertia.reload({ preserveState: false });
            }
        },

        getFilteredTasks(tasks, selectedTab) {
            return tasks.filter(task => {
                if (selectedTab === 0 || selectedTab === undefined) return true
                return task.sublist_id === selectedTab
            })
        },
        onSelectTab(key, value) {
            this.tabOptions[value] = key
            console.log('onSelectTab', key, value, this.tabOptions);
        },
        //new functions
        toggleSubcolumn(columnIndex, subIndex) {
            const sub = this.lists[columnIndex].sublist[subIndex];
            sub.isOpen = !sub.isOpen;
        },
        addSubcolumn(columnIndex) {
            const column = this.lists[columnIndex]
            column.newsubcolumn = !column.newsubcolumn;
        },
        toggleColumn(columnIndex) {
            const column = this.lists[columnIndex];
            column.isOpen = !column.isOpen;
        },
        showNewTask(columnIndex, subIndex) {
            const sub = this.lists[columnIndex].sublist[subIndex];
            sub.new_task_open = !sub.new_task_open;
        },
        createNewSubColumn(columnIndex) {
            const column = this.lists[columnIndex]
            column.loader = true;
            const request = this.formSublist;
            request.list_id = column.id;
            axios.post(this.route('sublist.new'), request).then((response) => {
                if (!response?.data?.error) {
                    const data = response.data.data
                    data.tasklist = [];
                    column.sublist.push(data);
                    column.newsubcolumn = false;

                }
            }).catch((error) => {
                console.log(error)
            }).finally(() => {
                column.loader = false;
            })
        },
        newTaskInSublist(columnIndex, subIndex) {

            const request = this.new_task;
            const sub = this.lists[columnIndex].sublist[subIndex];
            const tasks = sub.tasklist;
            request.sublist_id = sub.id;
            request.project_id = this.project.id;
            request.list_id = sub.list_id;
            sub.loader = true;

            axios.post(this.route('task.new'), request).then((response) => {
                if (response && response.data) {
                    tasks.push(response.data)
                    this.new_task.title = '';
                    sub.new_task_open = false;
                }
            }).catch((error) => {
                console.log(error)
            }).finally(() => {
                sub.loader = false;;
            })
        },
        saveNewSublist(sublistId, request) {
            axios.post(this.route('sublist.update', sublistId), request).then((response) => {
                console.log(response?.data)
                if (!response?.data?.error) {
                   // this.$inertia.reload({ preserveState: false });
                }
            }).catch((error) => {
                console.log(error)
            })
        },
        generateBasicStatus(){
            this.loaderBasicStatus = true;
            axios.post(this.route('project.generate.basicstatus', this.project.id), {}).then((response) => {
                if (response && response.data) {
                    
                }
            }).catch((error) => {
                console.log(error)
            }).finally(() => {
                this.loaderBasicStatus = false;
                this.$inertia.reload({ preserveState: false });
            })
        }
    },
}
</script>
<style scoped>
.noScroll {
    overflow-y: hidden;
    overflow-x: auto;
}

.option__task {
    top: 0px;
    right: 0px;
    z-index: 10;
    margin-top: 0.25rem;
    margin-right: 0.25rem;
    /* height: 1.25rem; */
    /* width: 1.25rem; */
    /* align-items: center; */
    justify-content: center;
    border-radius: 0.25rem;
    --tw-bg-opacity: 1;
    background-color: rgb(226 232 240 / var(--tw-bg-opacity));
    --tw-text-opacity: 1;
    color: rgb(51 65 85 / var(--tw-text-opacity));
    position: absolute;
}
</style>
