<template>
    <div class="h-full">
        <Head :title="__(title)" />
        <div class="flex workspace__view flex-col task__table overflow-hidden overflow-y-auto">
            <div class="min-w-full py-4 align-middle md:px-3 lg:px-4">

                <div class="flex justify-around relative items-center pt-3">
                    <div class="flex">
                        <div class="p-3 flex gap-2 items-center relative">
                            <div class="logo flex justify-center items-center w-9 h-9 rounded-full bg-indigo-600 text-white text-lg">
                                {{ workspace.name.charAt(0) }}
                            </div>
                            <div class="name">
                                {{ workspace.name }}
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex flex-wrap gap-2">
                        <div class="flex relative">
                            <button v-if="isAdmin" @click="invite_workspace = true" class="flex gap-[5px] bg-indigo-600 h-9 items-center text-white rounded px-3">
                                <icon name="user_plus" class="w-4 h-4 fill-white" />
                                Agregar participantes
                            </button>
                            <invite-workspace-member :workspace="workspace" v-if="invite_workspace" @invite-member="closeInviteMember()" top="40px" left="-10px" />
                        </div>
                        <button v-if="isAdmin" @click="showoptio = true" class="flex gap-[5px] bg-indigo-600 h-9 items-center text-white rounded px-3">
                            <icon name="user_plus" class="w-4 h-4 fill-white" />
                            Configurar columnas kanban
                        </button>
                    </div>
                    <button v-if="workspace.member.role === 'admin'" @click="show_more = !show_more" class="top-[50%] right-3 absolute show__more flex" v-click-outside="()=>{show_more = false}">
                        <icon class="w-4 w-4" name="more" />
                    </button>
                    <div v-if="show_more" class="absolute right-7 top-[50%] w-30 z-999 bg-gray-100">
                        <button @click="edit_workspace_option = true" class="flex w-full items-center bg-gray-200 hover:bg-gray-300 px-3 py-2 text-xs font-medium focus:outline-none focus:ring-0">
                            <icon class="mr-2 h-4 w-4" name="edit" /> Editar espacio de trabajo
                        </button>
                        <button @click="delete_workspace_popup = true" class="flex w-full items-center bg-gray-200 hover:bg-gray-300 px-3 py-2 text-xs font-medium focus:outline-none focus:ring-0">
                            <icon class="mr-2 h-4 w-4" name="trash" /> Eliminar espacio de trabajo
                        </button>
                    </div>
                </div>
                <div v-if="edit_workspace_option" class="z-[200] rounded-[8px] bg-white shadow overflow-hidden create__project">
                    <div class="flex gap-3 flex-col py-3 px-5">
                        <div class="flex">
                            <label class="w-full flex flex-col text-left">
                                <div>{{ __('Workspace name') }} *</div>
                                <input v-model="workspace.name" class="rounded border" type="text" required="" aria-required="true" autocomplete="off">
                            </label>
                        </div>
                        <div class="flex">
                            <label class="w-full flex flex-col text-left">
                                <div>{{ __('Website') }} <small>({{ __('optional') }})</small></div>
                                <input v-model="workspace.website" class="rounded border" type="text" autocomplete="off">
                            </label>
                        </div>
                        <div class="flex">
                            <label class="w-full flex flex-col text-left">
                                <div>{{ __('Workspace Description') }} <small>({{ __('optional') }})</small></div>
                                <textarea v-model="workspace.description" class="rounded border h-20" autocomplete="off" />
                            </label>
                        </div>
                        <div class="flex gap-3 justify-between">
                            <button class="bg-indigo-600 w-full text-white p-[9px] rounded disabled:opacity-50" :disabled="!workspace.name" @click="updateWorkspace()">
                                {{ __('Update') }}</button>
                            <button class="bg-indigo-600 w-full text-white p-[9px] rounded disabled:opacity-50" @click="edit_workspace_option = false">
                                {{ __('Cancel') }}</button>
                        </div>
                    </div>
                </div>
                <div class="flex px-2 w-full border-b my-5"></div>

                <h2 class="text mb-8 px-2 mt-6 text-[20px] font-medium">Proyecto</h2>

                <create-project v-if="create_project || editProject" 
                    @create-project="closeOption"
                    :edit="editProject"
                    :projectSelected="projectSelected"
                    @onSave="onUpdate"
                
                 />

                <ul class="project__list">
                    <li class="w-full py-1 px-2" v-if="!!this.$page.props.auth.user.role.create_project">
                        <button @click="create_project = true" class="p-2 group flex w-full rounded justify-between bg-cover bg-[#091e420f] hover:bg-[#091e4224]">
                            <div class="flex flex-col h-24 w-full justify-center text-[16px] font-bold text-[#172b4d]">
                                Crear nuevo proyecto
                            </div>
                        </button>
                    </li>
                    <li v-for="(project, project_index) in projects" class="w-full py-1 px-2">
                        <Link :href="route('projects.view.board', project.slug || project.id)" :style="{background: 'url('+project.background.image+')'}" class="p__item group">
                            <div class="content">
                                <div class="element">
                                    <div class="title">{{ project.title }}</div>
                                    <p class="details">{{ getDetails(project.description) }}</p>
                                </div>
                                <button class="flex w-7 h-7 items-center justify-center" @click="saveProject($event, project)">
                                    <icon v-if="!!project.star" name="star" class="w-5 h-5 fill-yellow-500 text-yellow-500 hover:fill-none hover:scale-125" />
                                    <icon v-else name="star" class="w-5 h-5 opacity-0 text-white group-hover:opacity-100 hover:text-yellow-500 hover:scale-125" />
                                </button>
                                <button class="flex w-7 h-7 items-center justify-center" @click="onClickEdit($event, project, project_index)">
                                    <icon  name="edit" class="w-5 h-5 fill-white   hover:scale-125" />
                                </button>
                            </div>
                        </Link>
                    </li>
                </ul>
            </div>
        </div>

        <delete-confirmation
            v-if="delete_workspace_popup" @popup="delete_workspace_popup = false" @confirm="deleteWorkspace()"
            details="Al eliminar el espacio de trabajo, se eliminarán todos los proyectos, incluida la lista de tableros. ¿Está seguro de que desea eliminar este espacio de trabajo?"
        />

         <!-- Overlay -->
        <div v-if="showoptio" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
            <!-- Modal -->
            <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
            <h2 class="text-lg font-semibold mb-2">¿Cómo se supervisan las actividades?</h2>
            <p class="text-sm text-gray-500 mb-4">
                Te ayudan a ver dónde están las tareas en el flujo de trabajo.
                Puedes cambiarlo en cualquier momento.
            </p>

            <!-- Lista de estados -->
            <div v-for="(estado, index) in estados" :key="index" class="flex items-center gap-2 mb-2">
                <input
                type="text"
                v-model="estados[index]"
                class="flex-1 border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
                <button @click="removeEstado(index)" class="text-gray-500 hover:text-red-500">
                ✕
                </button>
            </div>

            <!-- Botón agregar -->
            <button
                @click="addEstado"
                class="text-indigo-600 text-sm font-medium hover:underline mt-2"
            >
                + Agregar estado
            </button>

            <!-- Footer -->
            <div class="flex justify-end gap-2 mt-6">
                <button @click="showoptio = false" class="px-4 py-2 text-sm rounded border border-gray-300 hover:bg-gray-100">
                Cancelar
                </button>
                <button @click="showoptio = false" class="px-4 py-2 text-sm rounded bg-indigo-600 text-white hover:bg-indigo-700">
                Guardar
                </button>
            </div>
            </div>
        </div>
    </div>
</template>

<script>
import {Head, Link} from '@inertiajs/vue3'
import Layout from '@/Shared/Layout'
import Icon from '@/Shared/Icon'
import Pagination from '@/Shared/Pagination'
import BoardViewMenu from '@/Shared/BoardViewMenu'
import moment from 'moment'
import SearchInput from '@/Shared/SearchInput'
import CreateProject from "@/Shared/Modals/CreateProject";
import InviteWorkspaceMember from "../../Shared/Modals/InviteWorkspaceMember";
import axios from 'axios'
import DeleteConfirmation from "../../Shared/DeleteConfirmation";


export default {
    components: {
        DeleteConfirmation,
        InviteWorkspaceMember,
        CreateProject,
        Head,
        Icon,
        Link,
        BoardViewMenu,
        Pagination,
        SearchInput,
    },
    layout: Layout,
    props: {
        title: String,
        auth: Object,
        projects: Object,
        workspace: Object,
        filters: Object,
    },
    data() {
        return {
            create_project: false,
            delete_workspace_popup: false,
            edit_workspace_option: false,
            invite_workspace: false,
            show_more: false,
            form: {
                search: '',
            },
            editProject:false,
            projectSelected:{},
            project_index:-1,
            showoptio: false,
            estados: ['Backlog','Por hacer', 'En progreso', 'Hecho', 'Archivado'], // Estados predetermin
        }
    },
    computed: {
        isAdmin() {
            return this.auth?.user?.role?.slug === 'admin'
        },

    },
    created() {
        this.moment = moment
    },
    methods: {
        getDetails(text){
            if(text && text.length > 50)text = text.substring(0,50)+'...';
            return text;
        },
        deleteWorkspace(){
            this.$inertia.delete(this.route('workspace.destroy', this.workspace.id))
        },
        closeInviteMember(){
            this.invite_workspace = false
            window.location.href = this.route('workspace.members',this.workspace.slug || this.workspace.id);
        },
        updateWorkspace(){
            const data = {name: this.workspace.name, website: this.workspace.website, description: this.workspace.description};
            axios.post(this.route('json.workspace.update', this.workspace.id), data);
            this.edit_workspace_option = false;
        },
        saveProject(e, project){
            project.star = !project.star;
            e.preventDefault();
            axios.post(this.route('json.p.starred.save', project.id)).then((resp) => {
                // this.getProjects();
            });
        },
        onClickEdit(e,project, project_index){
            this.project_index = project_index
            this.editProject = true
            this.projectSelected = project
            e.preventDefault();
        },
        onUpdate(data){
            const item = this.projects[this.project_index];
            item.background = data.background
            item.description = data.description
            item.title = data.title
            item.folderKey = data.folderKey
            this.closeOption(this.projects[this.project_index])
        },
        closeOption(){
            this.create_project = false
            this.editProject = false
        },
         addEstado() {
            this.estados.push('')
        },
         removeEstado(index) {
            this.estados.splice(index, 1)
    }
    },
}
</script>
