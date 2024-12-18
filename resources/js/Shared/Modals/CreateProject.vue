<template>
    <div class="fixed top-[52px] w-[260px] left-[30%] z-[200] rounded-[8px] bg-white shadow overflow-hidden create__project" :style="{top: top, left: left}">
        <div class="flex gap-3 flex-col py-3 px-3" v-if="!loading">
            <div class="flex items-center justify-between gap-1">
                <div class="flex"></div>
                <div class="flex text-center" v-if="edit">
                    Editar proyecto
                </div>
                <div class="flex text-center" v-else>
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
                    <div>{{ __('Nombre proyecto') }} *</div>
                    <input v-model="project.title" class="rounded border" type="text" required="" aria-required="true" autocomplete="off">
                </label>
            </div>
            <div class="flex" v-show="!edit">
                <label class="flex flex-col w-full">
                    <div>{{ __('Espacio de trabajo') }}</div>
                    <select-input v-model="project.workspace_id" class=" mr-2 w-full">
                        <option :value="null">{{ __('Seleccione') }}</option>
                        <option v-for="(workspace, wi) in workspaces" :key="wi" :value="workspace.id">{{ workspace.name }}</option>
                    </select-input>
                </label>
            </div>
            <!-- <div class="flex">
                <div class="flex items-center h-5">
                    <input id="helper-checkbox" v-model="project.is_private" true-value="1" false-value="0" aria-describedby="helper-checkbox-text" type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600">
                </div>
                <div class="ms-1 text-sm">
                    <label for="helper-checkbox" class="font-medium text-[13px] text-gray-900 dark:text-gray-300">Tareas visibles para las personas asignadas <small>(Al habilitar esto, las tareas serán visibles solo para el administrador y las personas asignadas.)</small></label>
                </div>
            </div> -->
            <div class="flex">
                <label class="w-full flex flex-col">
                    <div>{{ __('Detalles de proyecto') }} <small>({{ __('opcional') }})</small></div>
                    <textarea v-model="project.description" class="rounded border" type="text" required="" aria-required="true" autocomplete="off" />
                </label>
            </div>
            <div class="flex">
                <label class="w-full flex flex-col text-left">
                    <div>ID Carpeta<small>(Google drive)</small></div>
                    <input v-model="project.folderKey" class="rounded border" type="text"  autocomplete="off">
                </label>
            </div>
            <div class="flex">
                <button 
                    class="bg-indigo-600 w-full text-white p-[9px] rounded disabled:opacity-50 flex items-center justify-center" 
                    :disabled="!project.title || saveLoader" 
                    @click="updateProject" 
                    v-if="edit"
                    >
                    <!-- Loader -->
                    <div  v-if="saveLoader" class="bg-opacity-80 flex inset-0 justify-center rounded-lg" >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="animate-spin size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                    </div>
                    <!-- Si no está cargando, muestra el texto del botón -->
                    <span v-else>Guardar</span>
                </button>
                <button class="bg-indigo-600 w-full text-white p-[9px] rounded disabled:opacity-50" 
                :disabled="!project.title" @click="createProject()" v-else>
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
        edit:{
            required: false,
            default: false
        },
        projectSelected:{
            required: false,
            default: {}
        },
    },
    components: { SelectInput, Icon },
    data() {
        return {
            project: {},
            loading: true,
            workspaces: [],
            backgrounds: [],
            saveLoader:false,
        }
    },
    methods: {
        async getData(){
            if(!this.edit){
                const workspaceResp = await axios.get(this.route('json.workspaces.all'));
                this.workspaces = workspaceResp.data;
            }
            
            const backgroundResp = await axios.get(this.route('json.backgrounds.all'));
            this.backgrounds = backgroundResp.data;
            this.project.color = this.backgrounds[0]
            this.loading = false;
            if(this.$page.props.workspace || this.$page.props.project){
                this.project.workspace_id = this.$page.props.workspace ? this.$page.props.workspace.id : this.$page.props.project? this.$page.props.project.workspace_id : '';
            }
            if(!this.workspaces.length && !this.edit){
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
        },
        async updateProject(){
            this.saveLoader = true
            const project = { ...this.project }
            project.background_id = project.color.id;
            delete project.color;
            const resp = await axios.post(this.route('project.update', this.projectSelected.id), project)
            this.saveLoader = false
            this.$emit('onSave', {...this.project, background: this.project.color})
           
        },
        setDataToForm(){
            this.project.title = this.projectSelected.title;
            this.project.color = this.projectSelected.background
            this.project.description = this.projectSelected.description
            this.project.folderKey = this.projectSelected.folderKey
            
        }
    },
    created() {
        if(this.edit){
            this.setDataToForm()
        }
        
        this.getData();
    },
}
</script>
