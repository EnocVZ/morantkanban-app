<template>
    <div class="layout-app" :class="[current_mode, $page.props.project?'project':'main', $page.component.replace('/', '_')]" :dir="$page.props.dir" :style="[$page.props.project?{backgroundColor: $page.props.project.background.bg, backgroundImage: 'url('+$page.props.project.background.image+')'}:{}]">
        <div id="dropdown" />
        <div class="md:flex md:flex-col">
            <div class="md:h-screen md:flex md:flex-col">
                <div class="md:flex md:shrink-0 ">
                    <div class="bg-white w-full p-4 md:py-2 md:pr-12 md:pl-8 text-sm flex justify-first items-center top_bar" :style="[$page.props.project?{backgroundColor: $page.props.project.background.top}:{}]">
                        <div class="placement-top-left w-full">
                            <div class="flex w-full lg:flex-row flex-col">
                                <div class="flex gap-3 select-none top_bar__menu">
                                    <Link class="mr-2" href="/">
                                        <logo class="site-logo white" name="white" />
                                        <logo class="site-logo color" />
                                    </Link>
                                    <div class="t__l__wrapper">
                                        <div class="mobile__menu__top bg-[#a6c5e229]" @click="show__menu__list = !show__menu__list">
                                            <span class="text-white">More</span> <icon class="ml-2 w-4 h-4 text-white" name="arrow-down" />
                                        </div>
                                        <div class="tl_menu_list hidden" :class="{'mobile': show__menu__list}">
                                            <div class="flex t__menu relative items-center cursor-pointer rounded py-1 px-3 hover:bg-[#a6c5e229]" v-click-outside="()=>{visible.menu_recent = false}" @click="visible['menu_recent'] = !visible['menu_recent']">
                                                <span class="text-white">{{ __('Recently Viewed') }}</span> <icon class="ml-2 w-4 h-4 text-white" name="arrow-down" />
                                                <top-project-menu v-if="visible.menu_recent" filter="recent" tabindex="-1" />
                                            </div>
                                            <div class="flex t__menu relative items-center cursor-pointer rounded py-1 px-3 hover:bg-[#a6c5e229]" v-click-outside="()=>{visible.menu_workspace = false}" @click="visible['menu_workspace'] = !visible['menu_workspace']">
                                                <span class="text-white">{{ __('My Workspaces') }}</span> <icon class="ml-2 w-4 h-4 text-white" name="arrow-down" />
                                                <top-workspace-menu v-if="visible.menu_workspace" tabindex="-1" />
                                            </div>
<!--                                            <div class="flex t__menu relative items-center cursor-pointer rounded py-1 px-3 hover:bg-[#a6c5e229]" v-click-outside="()=>{visible.menu_star = false}" @click="visible['menu_star'] = !visible['menu_star']">-->
<!--                                                <span class="text-white">{{ __('Starred') }}</span> <icon class="ml-2 w-4 h-4 text-white" name="arrow-down" />-->
<!--                                                <top-project-menu v-if="visible.menu_star" filter="star" tabindex="-1" />-->
<!--                                            </div>-->
                                        </div>
                                        <div v-if="this.$page.props.auth.user.role.create_project || this.$page.props.auth.user.role.create_workspace" class="__creation" v-click-outside="()=>{visible.menu_create = false}" @click="visible['menu_create'] = !visible['menu_create']">
                                            {{ __('Create') }}
                                            <section v-if="visible.menu_create" class="m__create">
                                                <div tabindex="-1" class="m__area">
                                                    <ul role="menu" class="">
                                                        <li v-for="create in creations" class="group">
                                                            <div v-if="create.condition" class="c__1" @click="visible[create.visible] = true">
                                                                <div class="c__2">
                                                                    <div class="c__3">
                                                                        <icon :name="create.icon" class="w-4 h-4" />
                                                                        <div>{{ create.name }}</div>
                                                                    </div>
                                                                    <div class="font-normal text-xs">{{ create.details }}</div>
                                                                </div>
                                                            </div>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </section>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="placement-top-right gap-2">
                            <div class="tracker" v-if="this.counter.timer && this.activeTimerString">
                                <p class="show">
                                    {{ activeTimerString }}
                                </p>
                                <button v-if="!!this.activeTimerString" @click="stopTracker()">{{ __('STOP') }}</button>
                                <Link :href="this.route('projects.view.board',{uid: this.counter.timer.task.project_id, task: this.counter.timer.task.slug || this.counter.timer.task.id})" aria-label="Detalles de la tarea"><icon class="" name="info" /></Link>
                            </div>
                            <button class="theme-toggle ml-3 mr-3" id="theme-toggle" title="Tema claro y oscuro" :aria-label="current_mode == 'dark' ? 'Oscuro': 'Claro'" aria-live="polite" @click="switchMode">
                                <svg class="sun-and-moon" aria-hidden="true" width="24" height="24" viewBox="0 0 24 24">
                                    <mask class="moon" id="moon-mask">
                                        <rect x="0" y="0" width="100%" height="100%" fill="white" />
                                        <circle cx="24" cy="10" r="6" fill="black" />
                                    </mask>
                                    <circle class="sun" cx="12" cy="12" r="6" mask="url(#moon-mask)" fill="currentColor" />
                                    <g class="sun-beams" stroke="currentColor">
                                        <line x1="12" y1="1" x2="12" y2="3" />
                                        <line x1="12" y1="21" x2="12" y2="23" />
                                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64" />
                                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78" />
                                        <line x1="1" y1="12" x2="3" y2="12" />
                                        <line x1="21" y1="12" x2="23" y2="12" />
                                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36" />
                                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22" />
                                    </g>
                                </svg>
                            </button>
                            <div class="relative inline-block">
                                <!-- Botón de notificación -->
                                <button
                                  @click="showDialog = true"
                                  class="relative inline-flex items-center justify-center p-2 text-white bg-blue-600 rounded-full hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                >
                                  <!-- Icono de campana (notification) -->
                                  <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="w-6 h-6"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                  >
                                    <path
                                      stroke-linecap="round"
                                      stroke-linejoin="round"
                                      stroke-width="2"
                                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-9.33-4.877M5 20h14"
                                    />
                                  </svg>
                            
                                  <!-- Burbuja de notificación (contador) -->
                                  <span
                                    v-if=" showNotificationWithoutRead.length > 0"
                                    class="absolute top-0 right-0 flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-600 rounded-full ring-2 ring-white"
                                  >
                                    {{ countNotification }}
                                  </span>
                                </button>
                            
                                <div v-if="showDialog" class="fixed top-0 right-0 w-96 h-full bg-white shadow-lg overflow-hidden modal-enter-active">
                                    <!-- Encabezado -->
                                    <div class="p-4 border-b flex justify-between items-center">
                                      <h2 class="text-xl font-semibold text-gray-800">Notificaciones</h2>
                                      <!-- Botón de Salir -->
                                      <button
                                        @click="showDialog = false"
                                        class="text-gray-400 hover:text-gray-600 focus:outline-none"
                                        aria-label="Cerrar"
                                      >
                                        ✖
                                      </button>
                                    </div>
                                
                                    <!-- Filtros de notificaciones>
                                    <div-- class="flex items-center justify-between px-4 py-2 border-b bg-gray-50">
                                      <div class="flex space-x-4">
                                        <button
                                          v-for="tab in tabs"
                                          :key="tab"
                                          @click="activeTab = tab"
                                          :class="{
                                            'text-blue-600 border-b-2 border-blue-600': activeTab === tab,
                                            'text-gray-500': activeTab !== tab
                                          }"
                                          class="px-2 py-1 text-sm font-medium focus:outline-none"
                                        >
                                          {{ tab }}
                                        </button>
                                      </div>
                                    </div-->
                                
                                    <!-- Barra de búsqueda>
                                    <div class="p-4 border-b">
                                      <input
                                        type="text"
                                        v-model="searchQuery"
                                        placeholder="Buscar notificaciones"
                                        class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring focus:ring-blue-300"
                                      />
                                    </div -->
                                
                                    <!-- Lista de notificaciones -->
                                    <div class="overflow-y-auto h-full">
                                        <div
                                            v-for="(notification, index) in taskToExpire"
                                            :key="index"
                                            :class="['bg-gray-200 flex items-start p-4 border-b hover:bg-gray-300']"
                                            >
                                            <!-- Imagen del usuario -->
                                            <div class="flex-shrink-0">
                                            <div class="h-10 w-10 bg-red-500 rounded-full flex items-center justify-center text-white font-bold">
                                                <span >EX</span>
                                            </div>
                                            </div>
                                
                                            <!-- Contenido de la notificación -->
                                            <div class="ml-4 flex-1">
                                            <p class="text-sm">
                                                <span class="font-semibold text-red-600">La tarea expira mañana</span></p>
                                            <p class="text-xs text-gray-400">Espacio de trabajo: {{ notification.project.workspace.name }}</p>
                                            <p class="text-xs text-gray-400">Proyecto: {{ notification.project.title }}</p>
                                            <div  v-html="notification.title"/>
                                            <button @click="sendWasRead(notification, false)" class="text-[14px] text-blue-500">ver</button>
                                            </div>
                                            
                                      </div>
                                        <div
                                            v-for="(notification, index) in notificationList"
                                            :key="index"
                                            :class="[{'bg-gray-100': notification.wasRead == 0}, 'flex items-start p-4 border-b hover:bg-gray-200']"
                                            >
                                            <!-- Imagen del usuario -->
                                            <div class="flex-shrink-0">
                                            <div :class="[{'bg-blue-500': notification.notification_type == 1,
                                                            'bg-green-500': notification.notification_type == 2,
                                                            'bg-gray-700': notification.notification_type == 3},
                                                            'h-10 w-10 rounded-full flex items-center justify-center text-white font-bold']">
                                                <span v-if="notification.notification_type == 1">CM</span>
                                                <span v-if="notification.notification_type == 2">AS</span>
                                                <span v-if="notification.notification_type == 3"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.633 10.25c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 0 1 2.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 0 0 .322-1.672V2.75a.75.75 0 0 1 .75-.75 2.25 2.25 0 0 1 2.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282m0 0h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 0 1-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 0 0-1.423-.23H5.904m10.598-9.75H14.25M5.904 18.5c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 0 1-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 9.953 4.167 9.5 5 9.5h1.053c.472 0 .745.556.5.96a8.958 8.958 0 0 0-1.302 4.665c0 1.194.232 2.333.654 3.375Z" />
                                                  </svg>
                                                </span>
                                            </div>
                                            </div>
                                
                                            <!-- Contenido de la notificación -->
                                            <div class="ml-4 flex-1">
                                            <p class="text-xs text-gray-400">Espacio de trabajo: {{ notification?.task?.project?.workspace?.name || "Ninguno"}}</p>
                                            <p class="text-xs text-gray-400">Proyecto: {{ notification?.task?.project?.title || "Ninguno"}}</p>
                                            <div  v-html="notification.title"/>
                                            <button @click="sendWasRead(notification)" class="text-[14px] text-blue-500">ver</button>
                                            </div>
                                
                                            <!-- Botón de eliminar -->
                                            <div class="ml-2">
                                                <button
                                                    @click="deleteNotification(notification.idtask_notification)"
                                                    class="text-red-500 hover:text-red-700 focus:outline-none"
                                                >
                                                    Eliminar
                                                </button>
                                            </div>
                                      </div>
                                      <div v-if="notificationList.length === 0" class="text-center text-gray-500 py-8">
                                        No hay notificaciones.
                                      </div>
                                    </div>
                                  </div><!--end mo0dal-->

                                </div>      
                            <dropdown class="select_user" placement="bottom-end">
                                <template #default>
                                    <div class="flex items-center cursor-pointer group">
                                        <div class="mr-1 whitespace-nowrap">
                                            <img v-if="$page.props.auth.user.photo" class="user_photo" :alt="$page.props.auth.user.first_name" :src="$page.props.auth.user.photo" />
                                            <img v-else src="/images/svg/profile.svg" class="w-5 h-5" alt="user profile" />
                                        </div>
                                        <icon class="w-5 h-5 drop-down-caret-icon fill-white" name="cheveron-down" />
                                    </div>
                                </template>
                                <template #dropdown>
                                    <div class="shadow-xl bg-white rounded text-sm ">
                                        <div class="flex px-4 flex-col py-3">
                                            <div class="uppercase mb-2 font-bold">Perfil</div>
                                            <div class="flex gap-1 items-center">
                                                <div class="flex">
                                                    <img v-if="$page.props.auth.user.photo" class="user_photo w-10 h-10" :alt="$page.props.auth.user.first_name" :src="$page.props.auth.user.photo" />
                                                    <img v-else src="/images/svg/profile.svg" class="w-10 h-10" alt="user profile" />
                                                </div>
                                                <div class="flex flex-col gap-[1px]">
                                                    <span>{{ $page.props.auth.user.first_name +' ' + $page.props.auth.user.last_name}}</span>
                                                    <small>{{ $page.props.auth.user.email }}</small>
                                                </div>
                                            </div>
                                        </div>
                                        <Link class="flex px-6 py-2 items-center hover:bg-indigo-500 hover:text-white hover:fill-white" :href="route('users.edit.profile')"><icon class="w-4 h-4 mr-2" name="user_edit" /> {{ __('Edit Profile') }}</Link>
                                        <Link v-if="$page.props.auth.user.role.slug === 'admin'" class="flex px-6 py-2 items-center hover:bg-indigo-500 hover:text-white hover:fill-white" :href="route('users')"><icon class="w-4 h-4 mr-2" name="settings" /> {{ __('Global Settings') }}</Link>
                                        <!-- Se modifico la ruta de acceso de global a users -->
                                        <Link class="flex items-center px-6 py-2 hover:bg-indigo-500 hover:text-white hover:fill-white w-full" :href="route('logout')" method="delete" as="button"><icon class="w-4 h-4 mr-2" name="logout" />{{ __('Logout') }}</Link>
                                    </div>
                                </template>
                            </dropdown>
                        </div>
                    </div>
                </div>
                <div class="md:flex md:flex-grow md:overflow-hidden">
                    <div v-if="!enable_sidebar" class="top-0 left-0 w-4 h-full left__bar" @click="enable_sidebar = true">
                        <div class="w-4 h-4 arr"><icon class="w-4 h-4" name="arrow-right" /></div>
                    </div>
                    <workspace-menu v-if="$page.props.project || $page.props.workspace" class="sidebar shrink-0 md:w-60 overflow-y-auto" @enableSidebar="enable_sidebar = false" :class="{'__hide':!enable_sidebar}" :style="[$page.props.project?{backgroundColor: $page.props.project.background.side}:{}]" />
                    <main-menu v-else-if="$page.props.auth.user.role.slug === 'admin'" class="hidden md:block sidebar shrink-0 md:w-60 overflow-y-auto" />

                    <div class="md:flex-1 md:overflow-y-auto" scroll-region>
                        <flash-messages />
                        <slot />
                    </div>
                </div>
                <create-project v-if="visible.project_create" @create-project="visible.project_create = false" />
                <create-workspace v-if="visible.create_workspace" @create-workspace="visible.create_workspace = false" />
            </div>
        </div>
    </div>
</template>

<script>
import Icon from '../Shared/Icon'
import Logo from '../Shared/Logo'
import Dropdown from '../Shared/Dropdown'
import MainMenu from './MainMenu'
import FlashMessages from './FlashMessages'
import TopProjectMenu from './TopProjectMenu'
import CreateProject from './Modals/CreateProject'
import { Link } from '@inertiajs/vue3'
import moment from 'moment'
import 'moment-duration-format';
import CreateWorkspace from "./Modals/CreateWorkspace";
import TopWorkspaceMenu from "./TopWorkspaceMenu";
import WorkspaceMenu from "./WorkspaceMenu";
import axios from 'axios'

export default {
    components: {
        WorkspaceMenu,
        TopWorkspaceMenu,
        CreateWorkspace,
        Dropdown,
        FlashMessages,
        Icon,
        Logo,
        Link,
        MainMenu,
        TopProjectMenu,
        CreateProject,
    },
    props: {
        title: String,
        auth: Object,
    },
    data() {
        return{
            creations: [
                {name: 'Proyecto', visible: 'project_create', icon: 'project',  condition: !!this.$page.props.auth.user.role.create_project, details: 'Después de crear el proyecto, podrás administrar tus tareas a bordo.'},
                {name: 'Espacio de trabajo', visible: 'create_workspace', condition: !!this.$page.props.auth.user.role.create_workspace, icon: 'workspace', details: 'Después de crear el proyecto, podrás administrar tus tareas a bordo.'},
            ],
            time: '',
            enable_sidebar: true,
            show__menu__list: false,
            current_mode: 'light',
            modes: ['dark', 'light'],
            visible: {project_create: false, create_workspace: false, menu_workspace: false, menu_recent: false, menu_star: false, menu_create: false},
            edit_route: '',
            current_page: 'dashboard',
            activeTimerString: '',
            counter: { seconds: 0, timer: this.auth.timer, duration: 0 },
            notificationList: [],
            showDialog:false,
            intervalId: null,
            taskToExpire:[],
            tabs: ["All", "Unread", "I was mentioned", "Assigned to me"],
            activeTab: "All",
            searchQuery: "",
                }
    },
    computed: {
        getNotification(){
            return this.$page.props.notification;
        },
        selected_language() {
            return this.$page.props.languages.find(language => language.code === this.$page.props.locale)
        },
        languages_except_selected(){
            return this.$page.props.languages.filter(language => language.code !== this.$page.props.locale)
        },
        getUser(){
            return this.$page.props.notification;
        },
        showNotificationWithoutRead(){
            return  this.notificationList.filter(notification => notification.wasRead == 0)
        },
        countNotification(){
            return this.showNotificationWithoutRead.length + this.taskToExpire.length;
        }
    },
    // $page.props.counter
    watch: {
        '$page.props.tracker': {
            handler() {
                if(this.$page.props.tracker){
                    if(!!this.$page.props.tracker.started && this.$page.props.counter){
                        this.startExistingTimer(this.$page.props.counter);
                    }else if(!this.$page.props.tracker.started && this.$page.props.counter){
                        this.stopTracker()
                    }
                }
            },
            deep: true,
        },
    },
    methods:{
        startExistingTimer(counter){
            Object.assign(this.counter, counter)
            let seconds = this.counter.timer.duration;
            this.counter.ticker = setInterval(() => {
                this.counter.seconds = ++seconds;
                this.activeTimerString = this.moment.duration(this.counter.seconds + parseInt(this.counter.duration), 'seconds').format()
            }, 1000)
        },
        goToLink(link){ window.location.href = link;},
        startTimer(){
            let started = this.counter.timer.started_at ? this.moment.utc(this.counter.timer.started_at) : this.moment();
            let seconds = parseInt(this.moment.duration(this.moment().diff(started)).asSeconds())
            seconds = this.counter.timer.duration + seconds;
            this.counter.ticker = setInterval(() => {
                this.counter.seconds = ++seconds;
                this.activeTimerString = this.moment.duration(this.counter.seconds + parseInt(this.counter.duration), 'seconds').format()
            }, 1000)
        },
        stopTracker(){
            axios.post(this.route('task.timer.stop'), { duration: this.counter.seconds, id: this.counter.timer.id }).then((response) => {
                this.counter.duration = response.data;
                this.stopTimer();
            })
        },
        stopTimer(){
            clearInterval(this.counter.ticker)
            this.activeTimerString = ''
            if(this.$page.props.lists){
                const task = this.counter.timer.task;
                const listIndex = this.$page.props.lists.findIndex(l=>l.id === task.list_id);
                if(listIndex > -1){
                    const taskIndex = this.$page.props.lists[listIndex].tasks.findIndex(t=>t.id === task.id)
                    if(taskIndex > -1) this.$page.props.lists[listIndex].tasks[taskIndex].timer = null;
                }
            }
        },
        switchMode(){
            this.current_mode = this.current_mode === 'light' ? 'dark' : 'light'
            localStorage.setItem('current_mode', this.current_mode)
        },
        async getDuration(task_id){
            const response = await axios.get(this.route('task.timer.duration', task_id));
            this.counter.duration = response.data;
            this.startTimer(this.counter.timer.started_at)
        },
        async getNotificationUser(){
            const {user} = this.auth;
            this.getTaskToExpire()
            const response = await axios.get(this.route('notification.assignees.user', user.id));
            this.notificationList = response.data
            
        },
        async getTaskToExpire(){
            const {user} = this.auth;
            const response = await axios.get(this.route('task.list.expre', user.id));
            this.taskToExpire = response.data
        },

        async sendWasRead(notification, updateRow = true){
            if(updateRow){
                const response = await axios.put(this.route('notification.wasread', notification.idtask_notification));
                window.location.href = this.route('projects.view.board',{uid:  notification.task.project_id, task:  notification.task.id})
            }else{
                window.location.href = this.route('projects.view.board',{uid:  notification.project_id, task:  notification.id})
            }
            
           
        },
        deleteNotification(id){
            const response =  axios.delete(this.route('notification.delete', id)).then(v=>{
                this.getNotificationUser()
            });
      }
    },
    created() {
        this.moment = moment;
        if(localStorage.getItem('current_mode')){
            this.current_mode = localStorage.getItem('current_mode')
        }

        if (this.counter.timer && this.counter.timer.started_at && !this.counter.timer.stopped_at){
            this.getDuration(this.counter.timer.task_id)
        }
        this.getNotificationUser()
        this.intervalId =  setInterval(this.getNotificationUser, 60000);
        
    },
    beforeDestroy() {
    if (this.intervalId) {
      clearInterval(this.intervalId);
    }
    },
}
</script>
<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: all 0.6s ease-in-out;
}
</style>
