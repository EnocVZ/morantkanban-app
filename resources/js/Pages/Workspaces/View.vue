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
                    <div class="flex relative">
                        <button v-if="workspace.member.role === 'admin'" @click="invite_workspace = true" class="flex gap-[5px] bg-indigo-600 h-9 items-center text-white rounded px-3">
                            <icon name="user_plus" class="w-4 h-4 fill-white" />
                            Invite Workspace members
                        </button>
                        <invite-workspace-member :workspace="workspace" v-if="invite_workspace" @invite-member="closeInviteMember()" top="40px" left="-10px" />
                    </div>
                    <button v-if="workspace.member.role === 'admin'" @click="show_more = !show_more" class="top-[50%] right-3 absolute show__more flex" v-click-outside="()=>{show_more = false}">
                        <icon class="w-4 w-4" name="more" />
                    </button>
                    <div v-if="show_more" class="absolute right-7 top-[50%] w-30 z-999 bg-gray-100">
                        <button @click="edit_workspace_option = true" class="flex w-full items-center bg-gray-200 hover:bg-gray-300 px-3 py-2 text-xs font-medium focus:outline-none focus:ring-0">
                            <icon class="mr-2 h-4 w-4" name="edit" /> Edit Workspace
                        </button>
                        <button @click="delete_workspace_popup = true" class="flex w-full items-center bg-gray-200 hover:bg-gray-300 px-3 py-2 text-xs font-medium focus:outline-none focus:ring-0">
                            <icon class="mr-2 h-4 w-4" name="trash" /> Delete Workspace
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
                                {{ __('Update') }} {{ __('Workspace') }}</button>
                            <button class="bg-indigo-600 w-full text-white p-[9px] rounded disabled:opacity-50" @click="edit_workspace_option = false">
                                {{ __('Cancel') }}</button>
                        </div>
                    </div>
                </div>
                <div class="flex px-2 w-full border-b my-5"></div>

                <h2 class="text mb-8 px-2 mt-6 text-[20px] font-medium">Projects</h2>

                <create-project v-if="create_project" @create-project="create_project = false" />

                <ul class="project__list">
                    <li class="w-full py-1 px-2" v-if="!!this.$page.props.auth.user.role.create_project">
                        <button @click="create_project = true" class="p-2 group flex w-full rounded justify-between bg-cover bg-[#091e420f] hover:bg-[#091e4224]">
                            <div class="flex flex-col h-24 w-full justify-center text-[16px] font-bold text-[#172b4d]">
                                Create new project
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
                            </div>
                        </Link>
                    </li>
                </ul>
            </div>
        </div>

        <delete-confirmation
            v-if="delete_workspace_popup" @popup="delete_workspace_popup = false" @confirm="deleteWorkspace()"
            details="Deleting workspace will delete all of the projects including board list. Are you sure you want to delete this workspace?"
        />
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
        }
    },
    computed: {

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
    },
}
</script>
