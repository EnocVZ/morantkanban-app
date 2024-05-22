<template>
    <div class="h-full">
        <Head :title="__(title)" />
        <div class="flex task__time_logs flex-col task__table h-[calc(100%-52px)] overflow-hidden overflow-y-auto">

            <div class="min-w-full py-4 align-middle md:px-3 lg:px-4">
                <div class="flex justify-around items-center pt-3">
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
                </div>
                <div class="flex px-2 w-full border-b my-5"></div>

                <div class="flex justify-between items-center">
                    <h2 class="text mb-1 px-2 text-[20px] font-medium">Team Members</h2>
                    <div class="tiny__time__log__bar">
                        <search-input v-model="form.search" class="w-full max-w-md mr-4" @reset="reset" />
                    </div>
                </div>
            </div>

            <div class="inline-block min-w-full py-4 align-middle md:px-3 lg:px-4">
                <div class="overflow-hidden border bg-white shadow-md rounded-lg border-gray-200 dark:border-gray-700 md:rounded-lg">
                    <table class="w-full rounded-lg whitespace-nowrap min-w-max w-full table-auto">
                        <thead>
                        <tr class=" text-gray-600 text-sm text-left">
                            <th class="px-6 pt-3 pb-2">{{ __('ID') }}</th>
                            <th class="px-6 pt-3 pb-2">{{ __('Member') }}</th>
                            <th class="px-6 pt-3 pb-2">{{ __('Role') }}</th>
                            <th class="px-6 pt-3 pb-2">{{ __('Created At') }}</th>
                            <th class="px-6 pt-3 pb-2">{{ __('Action') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(member, member_index) in team_members.data" :key="member.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
                            <td class="border-t">
                                <div class="px-6 py-3 flex items-center focus:text-indigo-500">
                                    {{ member.id }}
                                </div>
                            </td>
                            <td class="border-t">
                                <div class="px-6 py-3 flex items-center focus:text-indigo-500">
                                    <div v-if="member.photo" class="block rounded-full h-6 w-6">
                                        <img class="h-full w-full border border-white rounded-full" :src="member.photo" :alt="member.name">
                                    </div>
                                    <div class="ml-1">{{ member.name }}</div>
                                </div>
                            </td>
                            <td class="border-t">
                                <div class="px-6 py-3 flex capitalize items-center focus:text-indigo-500">
                                    {{ member.role }}
                                </div>
                            </td>
                            <td class="border-t">
                                <div class="px-6 py-3 flex items-center focus:text-indigo-500">
                                    {{ moment(member.created_at).format('MMM D, YYYY [at] h:mm a') }}
                                </div>
                            </td>
                            <td class="border-t">
                                <div class="px-6 py-3 flex items-center focus:text-indigo-500">
                                    <icon v-if="workspace.member.id !== member.id" @click="deleteMember(member, member_index)" name="trash" class="cursor-pointer w-4 h-4" />
                                </div>
                            </td>
                        </tr>
                        <tr v-if="team_members.data.length === 0">
                            <td class="border-t px-6 py-4" colspan="6">{{ __('No time log found.') }}</td>
                        </tr>
                        </tbody>
                    </table>
                    <div class="flex w-full px-3 pb-3">
                        <pagination class="mt-1" :links="team_members.links" />
                    </div>

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
import mapValues from "lodash/mapValues";
import throttle from "lodash/throttle";
import pickBy from "lodash/pickBy";
import axios from "axios";


export default {
    components: {
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
        team_members: Object,
        filters: Object,
    },
    data() {
        return {
            invite_workspace: false,
            form: {
                search: '',
            },
        }
    },
    watch: {
        form: {
            deep: true,
            handler: throttle(function() {
                this.$inertia.get(this.route('workspace.members', this.workspace.slug || this.workspace.id), pickBy(this.form), { preserveState: true })
            }, 150),
        },
    },
    computed: {

    },
    created() {
        this.moment = moment
    },
    methods: {
        reset() {
            this.form = mapValues(this.form, () => null)
        },
        closeInviteMember(){
            this.invite_workspace = false
            this.reset()
        },
        deleteMember(member, index){
            this.team_members.data.splice(index, 1);
            axios.post(this.route('json.workspace.member.add'), {workspace_id: this.workspace.id, user_id: member.user_id});
        },
    },
}
</script>
