<template>
    <div class="project__view__menu w-full p-2 text-sm flex justify-first items-center">
        <div class="inline-flex w-full flex-wrap items-center">
            <div class="view__menus flex items-center flex-start gap-1">
<!--                <div v-if="project.background" :style="{ 'background-image' : 'url('+ project.background.image +')' }" class="flex bg-cover rounded-full w-4 h-4 border"></div>-->
                <h2 class="text-lg font-bold hover:bg-[#a6c5e229] rounded px-3 mr-1 py-1" contenteditable="true" @keypress="saveListTitle($event, project.id)" @blur="saveListTitle($event, project.id)">{{ project.title }}</h2>
                <div class="flex p-2 items-center cursor-pointer rounded hover:bg-[#a6c5e229]" @click="starProject($event, project)">
                    <icon v-if="!!project.star" name="star" class="w-5 h-5 fill-yellow-500 text-yellow-500 hover:fill-none hover:scale-125" />
                    <icon v-else name="star" class="w-5 h-5 text-white hover:text-yellow-500 hover:scale-125" />
                </div>

                <Link v-for="(option, option_index) in options" class="flex py-2 px-3 items-center cursor-pointer capitalize rounded" :class="{ 'active': view === option.slug }" :href="route('projects.view.'+option.slug, project.slug || project.id)">
                    <icon :name="icons[option_index]" class="w-4 fill-[#ffffff] h-4 mr-[5px]" />
                    {{ __(option.name) }}
                </Link>
            </div>
            <div class="flex items-center flex-start gap-1 ml-auto view__menus">
                <!--button v-if="['board', 'table', 'time_logs'].includes(view)" class="flex pl-4 pr-2 items-center __filter cursor-pointer capitalize rounded hover:bg-[#a6c5e229]" @click="$emit('filterToggle')" :class="{'active': findFilters()}"> <icon name="filter" class="w-4 fill-[#ffffff] h-4 mr-[5px]" />
                    <span>{{ __('Filter') }} </span>
                    <span class="filter_clear" @click="clearFilter($event)">{{ __('Clear All') }} <icon name="close" class="w-4 h-4" /></span>
                </button-->
                <button v-if="['board', 'table'].includes(view)" @click="$emit('menuToggle', true)" class="flex px-2 h-8 items-center cursor-pointer capitalize rounded hover:bg-[#a6c5e229]"> <icon name="more-h" class="w-6 fill-[#ffffff] h-6" /></button>
            </div>
        </div>
    </div>
</template>

<script>
import Icon from '@/Shared/Icon'
import { Link } from '@inertiajs/vue3'
import BoardFilter from "./BoardFilter";
import axios from 'axios'
export default {
    name: 'board-view-menu',
    props: {
        project: Object,
        filters: { required: false },
        view: {
            required: false
        }
    },
    components: {BoardFilter, Icon, Link },
    data() {
        return {
            icons: ['board','calendar'],//,'table', 'dashboard',  'time'
            options: [
                {name: 'Board', slug: 'board'},
                {name: 'List', slug: 'table'},
                // {name: 'Report', slug: 'dashboard'},
                {name: 'Calendar', slug: 'calendar'},
                //{name: 'Time Logs', slug: 'time_logs'}
            ],
            position: {top: 0, left: 0, right: 'inherit'},
        }
    },
    methods: {
        clearFilter(e){
            e.preventDefault();
            e.stopPropagation()
            this.$emit('fClear', true);
        },
        findFilters(){
            const filters = Object.keys(this.filters);
            return filters.some(r=> ['due', 'label', 'user'].includes(r))
        },
        saveListTitle(e, id){
            if (e.keyCode === 13 || e.type === 'blur'){
                e.preventDefault();
                e.target.blur();
                if (e.target.innerText){
                    const title = e.target.innerText.replace(/[^a-zA-Z0-9 _-]/g, "");
                    axios.post(this.route('project.update', id),{ title })
                    this.project.title = title;
                }
            }
        },
        starProject(e, id){
            e.preventDefault();
            axios.post(this.route('json.p.starred.save', id));
            this.project.star = !this.project.star;
        }
    }
}
</script>
