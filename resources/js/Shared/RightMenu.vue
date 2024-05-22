<template>
    <div class="right__menu fixed w-[290px] z-[200] h-[calc(100%-45px)] shadow text-sm bg-[#ffffff] shadow overflow-hidden" :style="{top, left, right}">
        <div class="flex relative gap-3 flex-col py-3 px-3">
            <div class="flex items-center justify-between gap-1">
                <div class="flex">
                    <div v-if="show_back_button" class="top__control" @click="goBack()">
                        <icon name="arrow-left" class="w-4 h-4" />
                    </div>
                </div>
                <div class="flex text-center">
                    {{ __('Menu') }}
                </div>
                <div @click="$emit('menuToggle')" class="top__control">
                    <icon class="w-4 h-4" name="close" />
                </div>
            </div>
        </div>
        <ul class="buttons" v-if="!show_back_button">
            <li>
                <button @click="showItems('tasks')"><icon name="archive" /> {{ __('Archived Tasks') }}</button>
            </li>
            <li>
                <button @click="showItems('boards')"><icon name="archive" /> {{ __('Archived Board Items') }}</button>
            </li>
            <li v-if="$page.props.auth.user.role.slug === 'admin'" class="border-t mt-2 py-2">
                <button @click="showItems('backgrounds')"><span class="icon" :style="{backgroundImage: 'url('+project.background.image+')'}" /> {{ __('Change Background') }}</button>
            </li>
            <li v-if="$page.props.auth.user.role.slug === 'admin'">
                <button @click="showItems('workspaces')"><icon name="gear" />
                    {{ __('Change Workspace') }}</button>
            </li>
            <li v-if="$page.props.auth.user.role.slug === 'admin'">
                <button @click="showItems('visibility')"><icon name="display" />
                    {{ __('Change Task Visibility') }}</button>
            </li>
            <li v-if="$page.props.auth.user.role.slug === 'admin'">
                <button @click="delete_project_confirmation=true"><icon name="trash" />
                    {{ __('Delete Project') }}
                </button>
            </li>
            <li v-if="$page.props.auth.user.role.slug === 'admin'">
                <Link :href="route('global')"><icon name="settings" />
                    {{ __('Global Settings') }}
                </Link>
            </li>
        </ul>
        <div v-if="enable_options['tasks']" class="flex items-center flex-col px-2 w-full border-t pt-3">
            <div class="flex w-full" v-for="element in menu_data.tasks">
                <div @click="$emit('openTask', element.slug || element.id)" :data-id="element.id" class="flow-root p-2 w-full t__box border relative flex flex-col items-start mb-2 bg-white rounded-lg cursor-pointer bg-opacity-90 group hover:bg-opacity-100" draggable="true">
                    <h4 class="text-sm font-medium mb-1">{{ element.title }}</h4>
                    <div class="flex flex-wrap float-left items-center mb-1 text-xs font-medium gap-2 card__footer" :class="{'completed': element.checklist_done_count === element.checklists_count, 'done': element.is_done}">
                        <div class="flex items-center __item due" v-if="element.due_date" aria-label="Due date">
                            <icon class="w-4 h-4" name="time" />
                            <span class="pl-[2px] pr-[4px] leading-none"> {{ moment(element.due_date).format('MMM D') }} </span>
                        </div>
                        <div class="flex items-center __item" v-if="element.description" aria-label="This task has a description.">
                            <icon class="w-4 h-4" name="details" />
                        </div>
                        <div class="relative flex items-center __item" v-if="element.comments_count" aria-label="Comments">
                            <icon class="w-4 h-4" name="comment" />
                            <span class="ml-1 leading-none"> {{ element.comments_count }} </span>
                        </div>
                        <div class="flex items-center __item" v-if="element.attachments_count" aria-label="Attachments">
                            <icon class="w-4 h-4" name="attachment" />
                            <span class="ml-1 leading-none"> {{ element.attachments_count }} </span>
                        </div>
                        <div class="flex items-center __item check" v-if="element.checklists_count" aria-label="Checklist items">
                            <icon class="w-4 h-4" name="checklist" />
                            <span class="ml-1 leading-none"> {{ element.checklist_done_count+'/'+element.checklists_count }} </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex" v-if="!menu_data.tasks.length">{{ __('No task found!') }}</div>
        </div>
        <div v-if="enable_options['boards']" class="flex archive___board items-center flex-col px-2 w-full border-t pt-3">
            <div class="item" v-for="list in menu_data.boards">
                <div class="b__title">{{ list.title }}</div>
                <button class="btn" @click="sendToBoard(list.id)"><icon class="mr-1 h-3 w-3" name="undo" />
                    {{ __('Send to board') }}</button>
            </div>
            <div class="flex" v-if="!menu_data.boards.length">{{ __('No list found!') }}</div>
        </div>
        <div v-if="enable_options['workspaces']" class="flex archive___board items-center flex-col px-3 w-full border-t pt-3">
            <select-input v-model="selected_workspace" class=" w-full">
                <option :value="null">{{ __('Select a workspace') }}</option>
                <option v-for="(workspace, wi) in menu_data['workspaces']" :key="wi" :value="workspace.id">{{ workspace.name }}</option>
            </select-input>
            <button @click="changeWorkSpace(selected_workspace)" class="w-full mt-2 btn action justify-center">{{ __('Save') }}</button>
        </div>
        <div v-if="enable_options['visibility']" class="flex archive___board items-center flex-col px-3 w-full border-t pt-3">
            <div class="flex">
                <div class="flex items-center h-5">
                    <input id="helper-checkbox" v-model="project.is_private" aria-describedby="helper-checkbox-text" type="checkbox" true-value="1" false-value="0" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600">
                </div>
                <div class="ms-1 text-sm">
                    <label for="helper-checkbox" class="font-medium text-[14px] text-gray-900 dark:text-gray-300">{{ __('Visible tasks only for the assigned people.') }}</label>
                    <p class="pt-2">{{ __('Enabling this the tasks will be visible only for the admin and assigned people') }}</p>
                </div>
            </div>
            <button @click="changeProjectVisibility(project.is_private)" class="w-full mt-2 btn action justify-center">{{ __('Save') }}</button>
        </div>
        <div v-if="enable_options['backgrounds']" class="create__project flex px-3 w-full border-t pt-3">
            <label class=" flex flex-col">
                <div class="title mb-2">{{ __('Background') }}</div>
                <div class="color__list">
                    <ul class="grid grid-rows-2	grid-flow-col gap-[9px]">
                        <li v-for="color in menu_data['backgrounds']" class="flex">
                            <button @click="changeBackground(color)" class="w-10 h-8 flex items-center justify-center rounded" :style="{backgroundImage: 'url('+color.image+')', backgroundColor: color.bg}">
                                <icon v-if="selected_background.id === color.id" name="tick_check" class="text-white w-4 h-4" />
                            </button>
                        </li>
                    </ul>
                </div>
            </label>
        </div>
    </div>
    <delete-confirmation v-if="delete_project_confirmation" @popup="delete_project_confirmation = false" @confirm="deleteProject()"
                         details="Deleting project will delete all of the tasks including board list. Are you sure you want to delete this project?" />
</template>


<script>
import Icon from '@/Shared/Icon'
import { Link } from '@inertiajs/vue3'
import moment from 'moment'
import SelectInput from "./SelectInput";
import TaskDetails from "./Modals/TaskDetails";
import axios from 'axios'
import DeleteConfirmation from "./DeleteConfirmation";
export default {
    name: 'right-menu',
    props: {
        project: Object,
        top: { required: false, default: '45px' },
        left: { required: false, default: 'inherit' },
        right: { required: false, default: 0 }
    },
    emits :{
        openTask: null,
        menuToggle: null,
    },
    components: {DeleteConfirmation, TaskDetails, SelectInput, Icon, Link },
    data() {
        return {
            enable_options: { tasks: false, boards: false, workspaces: false, backgrounds: false, visibility: false },
            show_back_button: false,
            delete_project_confirmation: false,
            selected_workspace: this.project.workspace_id,
            selected_background: this.project.background,
            menu_data: { tasks: [], boards: [], workspaces: [], backgrounds: [] }
        }
    },
    methods: {

        changeBackground(background){
            this.selected_background = background;
            this.project.background = background;
            axios.post(this.route('project.update', this.project.id), {background_id: background.id})
            if(this.$page.props.project){
                this.$page.props.project.background = background;
            }
        },
        async changeProjectVisibility(is_private){
            await axios.post(this.route('project.update', this.project.id), {is_private})
            window.location.href = this.route('projects.view.board', this.project.slug || this.project.id)
        },
        deleteProject(){
            this.delete_project_confirmation = false;
            this.$emit('menuToggle')
            this.$inertia.delete(this.route('project.destroy', this.project.id))
        },
        goBack(){
            const options = this.enable_options;
            Object.keys(options).forEach(function(key) {
                options[key] = false;
            });
            this.show_back_button = false;
        },
        showItems(type){
            this.enable_options[type] = true;
            this.show_back_button = true;
            if(type !== 'visibility'){
                this.getItem(type);
            }
        },
        async getItem(type){
            const archiveData = await axios.get(this.route('json.menu_data.'+type, this.project.id));
            this.menu_data[type] = archiveData.data;
        },
        async sendToBoard(id){
            await axios.post(this.route('json.board.remove.archive', id));
            await this.getItem('boards');
        },
        async changeWorkSpace(workspace_id){
            await axios.post(this.route('json.workspace.change'), {workspace_id, project_id: this.project.id});
            window.location.href = this.route('projects.view.board', this.project.slug || this.project.id)
        }
    },
    created() {
        this.moment = moment
    },
}
</script>
