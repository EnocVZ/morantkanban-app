<template>
    <div class="fixed top-[52px] w-[260px] left-[30%] z-[200] rounded-[8px] bg-white shadow overflow-hidden create__project" :style="{top: top, left: left}">
        <div class="flex gap-3 flex-col py-3 px-3" v-if="!loading">
            <div class="flex items-center justify-between gap-1">
                <div class="flex"></div>
                <div class="flex text-center">
                    {{ __('Create Project') }}
                </div>
                <div @click="$emit('createProject')" class="flex hover:bg-gray-200 cursor-pointer rounded w-7 h-7 justify-center items-center">
                    <icon class="w-4 h-4" name="close" />
                </div>
            </div>
            <div class="flex justify-center">
                <div class="w-[70%] h-[100px] p-3 flex rounded justify-center" :style="{backgroundImage: 'url('+project.color.image+')', backgroundColor: project.color.bg}">
                    <img src="/images/board.svg" class="w-auto max-h-full" alt="Board" />
                </div>
            </div>
            <div class="flex">
                <label class=" flex flex-col">
                    <div class="title mb-2">{{ __('Background') }}</div>
                    <div class="color__list">
                        <ul class="grid grid-rows-2	grid-flow-col gap-[9px]">
                            <li v-for="color in backgrounds" class="flex">
                                <button @click="project.color = color" class="w-10 h-8 flex items-center justify-center rounded" :style="{backgroundImage: 'url('+color.image+')', backgroundColor: color.bg}">
                                    <icon v-if="project.color.id === color.id" name="tick_check" class="text-white w-4 h-4" />
                                </button>
                            </li>
                        </ul>
                    </div>
                </label>
            </div>
            <div class="flex">
                <label class="w-full flex flex-col text-left">
                    <div>{{ __('Project name') }} *</div>
                    <input v-model="project.title" class="rounded border" type="text" required="" aria-required="true" autocomplete="off">
                </label>
            </div>
            <div class="flex">
                <label class="flex flex-col w-full">
                    <div>{{ __('Workspace') }}</div>
                    <select-input v-model="project.workspace_id" class=" mr-2 w-full">
                        <option :value="null">{{ __('Select a workspace') }}</option>
                        <option v-for="(workspace, wi) in workspaces" :key="wi" :value="workspace.id">{{ workspace.name }}</option>
                    </select-input>
                </label>
            </div>
            <div class="flex">
                <div class="flex items-center h-5">
                    <input id="helper-checkbox" v-model="project.is_private" true-value="1" false-value="0" aria-describedby="helper-checkbox-text" type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600">
                </div>
                <div class="ms-1 text-sm">
                    <label for="helper-checkbox" class="font-medium text-[13px] text-gray-900 dark:text-gray-300">Visible tasks for assigned people <small>(Enabling this the tasks will be visible only for the admin and assigned people)</small></label>
                </div>
            </div>
            <div class="flex">
                <label class="w-full flex flex-col">
                    <div>{{ __('Project Details') }} <small>({{ __('optional') }})</small></div>
                    <textarea v-model="project.description" class="rounded border" type="text" required="" aria-required="true" autocomplete="off" />
                </label>
            </div>
            <div class="flex">
                <button class="bg-indigo-600 w-full text-white p-[9px] rounded disabled:opacity-50" :disabled="!project.title" @click="createProject()">
                    {{ __('Create') }}</button>
            </div>
        </div>
    </div>
</template>

<script>
import SelectInput from '@/Shared/SelectInput'
import Icon from '@/Shared/Icon'
import axios from 'axios'
export default {
    name: "create-project",
    props: {
        top: {
            required: false,
            default: '50px'
        },
        left: {
            required: false,
            default: '390px'
        },
    },
    components: { SelectInput, Icon },
    data() {
        return {
            project: {},
            loading: true,
            workspaces: [],
            backgrounds: [],
        }
    },
    methods: {
        async getData(){
            const workspaceResp = await axios.get(this.route('json.workspaces.all'));
            this.workspaces = workspaceResp.data;
            const backgroundResp = await axios.get(this.route('json.backgrounds.all'));
            this.backgrounds = backgroundResp.data;
            this.project.color = this.backgrounds[0]
            this.loading = false;
            if(this.$page.props.workspace || this.$page.props.project){
                this.project.workspace_id = this.$page.props.workspace ? this.$page.props.workspace.id : this.$page.props.project? this.$page.props.project.workspace_id : '';
            }
            if(!this.workspaces.length){
                alert('You must need to create/join a workspace first.')
                this.$emit('createProject')
            }
        },
        createProject(){
            const project = { ...this.project }
            project.background_id = project.color.id;
            delete project.color;
            axios.post(this.route('json.project.create'), project).then((response) => {
                if(response.data){
                    window.location = this.route('projects.view.board', response.data.slug || response.data.id);
                }
            });
        }
    },
    created() {
        this.getData();
    },
}
</script>
