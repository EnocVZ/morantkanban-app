<template>
    <div class="fixed inset-0 bg-black bg-opacity-80 z-[199] flex items-center justify-center">
        <div
            class="fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 z-[200] rounded-[8px] bg-white shadow overflow-hidden">
            <div class="flex gap-3 flex-col py-3 px-5" v-if="!loading">
                <div class="flex items-center justify-between gap-1">
                    <div class="flex"></div>
                    <div class="flex text-center">
                        {{ __('Mover a otro espacio de trabajo') }}
                    </div>
                    <div @click="$emit('onClose')"
                        class="flex hover:bg-gray-200 cursor-pointer rounded w-7 h-7 justify-center items-center">
                        <icon class="w-4 h-4" name="close" />
                    </div>
                </div>
                <div class="flex">
                    <div role="status" class="max-w-sm animate-pulse" v-if="loadWorkspaces">
                        <div class="h-2.5 bg-gray-200 rounded-full dark:bg-gray-700 w-48 mb-4"></div>
                    </div>
                    <label class="flex flex-col w-full text-left" v-else>
                        <div>{{ __('Espacio de trabajo') }}</div>
                        <select-input v-model="idWorkspace" class=" mr-2 w-full">
                            <option v-for="(wkspace, ti) in workspaces" :key="ti" :value="wkspace.id">{{ wkspace.name }}</option>
                        </select-input>
                    </label>
                </div>
                <div class="flex">
                    <div role="status" class="max-w-sm animate-pulse" v-if="loadProjects">
                        <div class="h-2.5 bg-gray-200 rounded-full dark:bg-gray-700 w-48 mb-4"></div>
                    </div>
                    <label class="flex flex-col w-full text-left" v-else>
                        <div>{{ __('Proyecto') }}</div>
                        <select-input v-model="idProject" class=" mr-2 w-full">
                            <option v-for="(project, ti) in projects" :key="ti" :value="project.id">{{ project.title }}</option>
                        </select-input>
                    </label>
                </div>
                <div class="flex">
                    <div role="status" class="max-w-sm animate-pulse" v-if="loadBoardList">
                        <div class="h-2.5 bg-gray-200 rounded-full dark:bg-gray-700 w-48 mb-4"></div>
                    </div>
                    <label class="flex flex-col w-full text-left" v-else>
                        <div>{{ __('Lista') }}</div>
                        <select-input v-model="idBoard" class=" mr-2 w-full">
                            <option v-for="(board, ti) in boardList" :key="ti" :value="board.id">{{ board.title }}</option>
                        </select-input>
                    </label>
                </div>
                <div class="flex">
                    <form-button @click="changeWorkSpace" label="Mover" :loading="loaderSave" loaderLabel="Moviendo" 
                     :disabled="!validButtonSave" v-show="optionToSave != 1"/>
                    
                    <div class="px-4 py-2 text-green-700" role="alert" v-if="optionToSave == 1">
                    <span class="block sm:inline">Se movió correctamente.</span>
                    </div>
                    <div class="px-4 py-2 text-red-700" role="alert" v-if="optionToSave == 2">
                    <span class="block sm:inline">Algo salió mal, intenta de nuevo o mas tarde.</span>
                    </div>

                </div>
            </div>
        </div>
    </div>

</template>

<script>
import SelectInput from '@/Shared/SelectInput'
import FormButton from '@/Shared/FormButton'
import Icon from '@/Shared/Icon'
import axios from 'axios'
export default {
    name: "change-workspace",
    props: {
        top: {
            required: false,
            default: '50px'
        },
        left: {
            required: false,
            default: '390px'
        },
        taskId: {
            type: Number,
            required: true
        }
    },
    components: { SelectInput, Icon, FormButton },
    computed:{
        validButtonSave() {
            return this.idWorkspace > 0 && this.idProject > 0 && this.idBoard > 0;
        }
    },
    emits: {
        onClose: null
    },
    data() {
        return {
            workspace: {},
            loading: false,
            loaderSave: false,
            loadProjects: false,
            loadBoardList: false,
            loadWorkspaces: false,
            optionToSave: 0, // 0: no se ha guardado, 1: se guardó correctamente, 2: error al guardar
            workspaces: [],
            backgrounds: [],
            projects: [],
            boardList: [],
            idWorkspace: 0,
            idProject: 0,
            idBoard: 0,
            types: ['Desarrollo ', 'Ciencia de datos', 'IA', ' Encuestas', 'Ventas', 'Otros'],
        }
    },
     watch: {
        idWorkspace(newValue) {
            this.getProjectsByWorkspace(newValue);
        },
        idProject(newValue) {
            this.getBoardListByProject(newValue);
        }
        },
       
    methods: {
        getAllWorkspaces() {
            axios.get(this.route('json.workspaces.all')).then((response) => {
                this.workspaces = response.data;
            });
        },
        getProjectsByWorkspace(workspaceId) {
            this.loadProjects = true;
            this.loadBoardList = true;
            this.idProject = 0;
            this.idBoard = 0;
            axios.get(this.route('json.projects.all', workspaceId)).then((response) => {
                this.projects = response.data;
            }).finally(()=>{
                this.loadProjects = false;
                this.loadBoardList = false;
            });
        },
        getBoardListByProject(projectId) {
            this.loadBoardList = true;
            axios.get(this.route('boardlist.all', projectId)).then((response) => {
                this.boardList = response.data.data;
            }).finally(() => {
                this.idBoard = 0;
                this.loadBoardList = false;
            });
        },
        changeWorkSpace() {
            const REQUEST = {
               list_id: this.idBoard,
               project_id: this.idProject,
            };
            this.loaderSave = true;
            axios.post(this.route('task.list.change', this.taskId), REQUEST).then((response) => {
                if (!response.data.error) {
                    this.optionToSave = 1;
                    this.$emit('onClose', true);
                } else {
                   this.optionToSave = 2;
                   
                }
            }).finally(() => {
                this.loaderSave = false;
            });
        }
    },
    created() {
        this.getAllWorkspaces();
    },
}
</script>
