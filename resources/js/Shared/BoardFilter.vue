<template>
    <div class="absolute w-[250px] z-[200] rounded-[8px] text-sm bg-white shadow overflow-hidden" :style="{top, left, right}">
        <div class="flex gap-3 flex-col py-3 px-3" v-if="!loading">
            <div class="flex items-center justify-between gap-1">
                <div class="flex"></div>
                <div class="flex text-center">
                    {{ __('Filter') }}
                </div>
                <div @click="$emit('boardFilter')" class="flex hover:bg-gray-200 cursor-pointer rounded w-7 h-7 justify-center items-center">
                    <icon class="w-4 h-4" name="close" />
                </div>
            </div>
            <div v-if="enable_options.includes('user')">
                <div class="name my-2 font-medium">{{ __('Members') }}</div>
                <ul class="flex flex-col gap-3 h-auto max-h-40 overflow-y-auto">
                    <li>
                        <label class="flex items-center cursor-pointer" for="filter__user__no">
                            <input id="filter__user__no" class="w-4 h-4 mr-1" type="checkbox" :checked="filterOptions('user', 'null')" @change="doFilter($event.target.checked, 'user', 'null')">
                            <icon class="w-4 h-4 mr-1" name="user" />
                            <span class="flex" type="button">
                             {{ __('No members') }}
                        </span>
                        </label>
                    </li>
                    <li>
                        <label class="flex items-center cursor-pointer" for="filter__user__me">
                            <input id="filter__user__me" class="w-4 h-4 mr-1" type="checkbox" :checked="filterOptions('user', $page.props.auth.user.id)" @change="doFilter($event.target.checked, 'user', $page.props.auth.user.id)">
                            <img v-if="$page.props.auth.user.photo" class="w-4 h-4 mr-1" :alt="$page.props.auth.user.first_name" :src="$page.props.auth.user.photo" />
                            <img v-else src="/images/svg/profile.svg" class="w-4 h-4 mr-1" alt="user profile" />
                            <span class="flex">
                            {{ __('Tasks assigned to me') }}
                        </span>
                        </label>
                    </li>
                    <li v-for="(userObject, user_index) in users">
                        <label :for="'uid_'+user_index" class="flex items-center cursor-pointer">
                            <input :id="'uid_'+user_index" class="w-4 h-4 mr-1" type="checkbox" :checked="filterOptions('user', userObject.id)" @change="doFilter($event.target.checked, 'user', userObject.id)">
                            <img v-if="userObject.photo_path" :aria-label="userObject.name" :alt="userObject.name" class="w-4 h-4 mr-1 rounded-full" :src="userObject.photo_path" />
                            <img v-else :aria-label="userObject.name" :alt="userObject.name" class="w-4 h-4 mr-1 rounded-full" src="/images/user.svg" />
                            <span data-a="" class="" type="button" :tabindex="user_index">{{ userObject.name }}</span>
                        </label>
                    </li>
                </ul>
            </div>
            <div v-if="enable_options.includes('due')">
                <div class="name my-2">{{ __('Due Date') }}</div>
                <ul class="flex flex-col gap-3">
                    <li>
                        <label class="flex gap-1 items-center" for="filter__due__no">
                            <input id="filter__due__no" class="w-4 h-4 mr-1 cursor-pointer" type="checkbox" :checked="filterOptions('due', 'null')" @change="doFilter($event.target.checked, 'due', 'null')">
                            <span class="flex items-center" type="button">
                            <span class="bg-gray-100 mr-1 p-1 rounded-full"><icon class="w-3 h-3 text-[#ffffff]" name="calendar" /></span> {{ __('No dates') }}
                        </span>
                        </label>
                    </li>
                    <li>
                        <label class="flex gap-1 items-center" for="filter__due__over">
                            <input id="filter__due__over" class="w-4 h-4 mr-1 cursor-pointer" type="checkbox" :checked="filterOptions('due', 'over')" @change="doFilter($event.target.checked, 'due', 'over')">
                            <span class="flex items-center" type="button">
                            <span class="bg-[#c9372c] mr-1 p-1 rounded-full"><icon class="w-3 h-3 text-[#ffffff]" name="clock" /></span> {{ __('Overdue') }}
                        </span>
                        </label>
                    </li>
                    <li>
                        <label class="flex gap-1 items-center" for="filter__due__next_day">
                            <input id="filter__due__next_day" class="w-4 h-4 mr-1 cursor-pointer" type="checkbox" :checked="filterOptions('due', 'next_day')" @change="doFilter($event.target.checked, 'due', 'next_day')">
                            <span class="flex items-center" type="button">
                            <span class="bg-[#e56910] mr-1 p-1 rounded-full"><icon class="w-3 h-3 text-[#ffffff]" name="clock" /></span> {{ __('Due in the next day') }}
                        </span>
                        </label>
                    </li>
                </ul>
            </div>
            <div v-if="enable_options.includes('label')">
                <div class="name my-2">{{ __('Labels') }}</div>
                <ul class="flex flex-col gap-3 max-h-40 overflow-y-auto">
                    <li v-for="(lab, lab_index) in labels">
                        <label class="flex items-center gap-1" :for="'f_l_'+lab_index">
                            <input :id="'f_l_'+lab_index" class="w-4 h-4 mr-1 cursor-pointer" type="checkbox" :checked="filterOptions('label',lab.id)" @change="doFilter($event.target.checked, 'label', lab.id )">
                            <span class="w-full px-2 py-1 rounded cursor-pointer hover:opacity-80" :style="{background: lab.color}" :tabindex="lab_index" data-color="orange">{{ lab.name }}</span>
                        </label>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script>
import Icon from '@/Shared/Icon'
import { Link } from '@inertiajs/vue3'
import axios from 'axios'
export default {
    name: 'board-filter',
    props: {
        project: Object,
        filters: { required: false },
        options: { required: false },
        top: { required: false, default: '95px' },
        left: { required: false, default: 'inherit' },
        right: { required: false, default: '20px' }
    },
    components: { Icon, Link },
    emits :{
        doFilter: null,
        boardFilter: null,
    },
    data() {
        return {
            open_filter: false,
            user_search: '',
            loading: true,
            users: [],
            filter_options: [],
            filtered_user: [],
            labels: [],
            selected_labels: [],
            enable_options: [],
            form: {
                user: this.filters.user,
                due: this.filters.due,
                label: this.filters.label,
            },
        }
    },
    methods: {
        filterOptions(type, value){
            if(this.filters[type]){
                const filters = this.filters[type].split(',');
                return filters.includes(String(value));
            }
        },
        doFilter(bool, type, val){
            this.addOrRemove(type, val)
        },
        addOrRemove(el, val){
            val = String(val);
            if(!!this.form[el]){
                const arr = String(this.form[el]).split(',')
                if(!arr.includes(val)){
                    arr.push(val);
                }else{
                    const findIndex = arr.findIndex(u=>u === val);
                    arr.splice(findIndex, 1);
                }
                this.form[el] = arr.join();
            }else{
                this.form[el] = val;
            }
            this.$emit('doFilter', this.form)
        },
        searchLabel(input){
            return this.labels.filter(lab => lab.name.toLowerCase().indexOf(input) > -1);
        },
        filterTask(bool, user_id, type){
            if(type === 'user'){

            }else{
                this.filter_options.push(type)
            }
        },
        getData(){
            axios.get(this.route('json.project.filter.data', this.project.id)).then((response)=>{
                const data = response.data;
                this.users = data.assignees.map(user=>{return {name: user.user.name, id: user.user_id}})
                this.labels = data.labels
                this.loading = false;
            })
        },
    },
    created() {
        this.getData();
        this.enable_options = this.options.split(',')
    },
}
</script>
