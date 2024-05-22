<template>
    <section class="top_project_menu">
        <div v-if="loading" role="status" class="project__loading" v-for="li in [1,2,3,4,5]">
            <div class="p__image">
                <icon name="pulse_image" />
            </div>
            <div class="__bar">
                <div />
            </div>
            <span class="sr-only">Loading...</span>
        </div>
        <div v-else tabindex="-1" class="menu__wrapper">
            <ul role="menu" class="list">
                <li v-for="(workspace, p_index) in workspaces" class="item group" :key="workspace.id">
                    <div class="content">
                        <Link class="flex" :href="route('workspace.view', workspace.slug || workspace.id)">
                            <div class="p-2 flex gap-2 items-center">
                                <div class="logo flex justify-center items-center w-6 h-6 rounded-full bg-indigo-600 text-white text-sm">
                                    {{ workspace.name.charAt(0) }}
                                </div>
                                <div class="font-medium text-sm leading-[18px]">
                                    {{ workspace.name }}
                                </div>
                            </div>
                        </Link>
                    </div>
                </li>
                <li v-if="!workspaces.length" class="flex"><div class="flex px-2 py-2">{{ __('No item found!') }}</div></li>
            </ul>
        </div>
    </section>
</template>

<script>
import { Link } from '@inertiajs/vue3'
import Icon from '@/Shared/Icon'
import axios from 'axios'

export default {
    name: "top-workspace-menu",
    components: { Link, Icon },
    data() {
        return {
            loading: true,
            your_workspaces: [],
            guest_workspaces: [],
            workspaces: [],
        }
    },
    methods: {
        getWorkspaces(){
            axios.get(this.route('json.workspaces.all')).then((response) => {
                if(response.data){
                    this.workspaces = response.data
                    this.loading = false;
                }
            });
        },
    },
    created() {
        this.getWorkspaces()
    },
}
</script>
