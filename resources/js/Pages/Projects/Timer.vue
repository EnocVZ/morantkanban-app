<template>
    <div class="h-full">
        <Head :title="__(title)" />

        <board-view-menu :project="project" @filter-toggle="open_filter = !open_filter" @fClear="reset()" :filters="filters" view="time_logs" />
        <board-filter :project="project" @board-filter="open_filter = false" :filters="filters" v-if="open_filter" @do-filter="doFilter" options="user"  />
        <div class="flex task__time_logs flex-col task__table h-[calc(100%-52px)] overflow-hidden overflow-y-auto">
            <div class="inline-block min-w-full py-4 align-middle md:px-3 lg:px-4">
                <div class="tiny__time__log__bar flex justify-between">
                    <search-input v-model="form.search" class="w-full max-w-md mr-4" @reset="reset" />
                    <div class="flex justify-start" v-if="total_duration">
                        <h4 class="text-[#ffffff] text-lg mr-2">{{ __('Total duration') }}: {{ moment.duration(total_duration, 'seconds').format('h[h] m[m] s[s]') }}</h4>
                    </div>
                </div>
                    <div class="table__view">
                        <table>
                            <tr class=" text-gray-600 text-sm text-left">
                                <th class="px-6 pt-3 pb-2">{{ __('Task') }}</th>
                                <th class="px-6 pt-3 pb-2">{{ __('Member') }}</th>
                                <th class="px-6 pt-3 pb-2">{{ __('Started') }}</th>
                                <th class="px-6 pt-3 pb-2">{{ __('Stopped') }}</th>
                                <th class="px-6 pt-3 pb-2">{{ __('Duration') }}</th>
                                <th class="px-6 pt-3 pb-2">{{ __('Memo') }}</th>
                            </tr>
                            <tbody>
                            <tr v-for="log in time_logs.data" :key="log.id" class="hover:bg-gray-100 focus-within:bg-gray-100 leading-5">
                                <td class="border-t">
                                    <Link :href="this.route('projects.board.with.task', {projectUid: project.slug || project.id, taskUid: log.task.slug || log.task.id})" class="px-6 py-3 flex items-center focus:text-indigo-500">
                                        {{ log.task.title }}
                                    </Link>
                                </td>
                                <td class="border-t">
                                    <div class="px-6 py-3 flex items-center focus:text-indigo-500">
                                        <div class="block rounded-full h-6 w-6">
                                            <img class="h-full w-full border border-white rounded-full" :src="log.user.photo_path" :alt="log.user.name">
                                        </div>
                                        <div class="ml-1">{{ log.user.name }}</div>
                                    </div>
                                </td>
                                <td class="border-t">
                                    <div class="px-6 py-3 flex items-center focus:text-indigo-500">
                                        {{ moment(log.started_at).format('MMM D, YYYY [at] h:mm a') }}
                                    </div>
                                </td>
                                <td class="border-t">
                                    <div class="px-6 py-3 flex items-center focus:text-indigo-500">
                                        {{ moment(log.stopped_at).format('MMM D, YYYY [at] h:mm a') }}
                                    </div>
                                </td>
                                <td class="border-t w-px">
                                    <div class="px-6 py-3 flex items-center focus:text-indigo-500">
                                        {{ moment.duration(log.duration, 'seconds').format('h[h] m[m] s[s]') }}
                                    </div>
                                </td>
                                <td class="border-t w-px">
                                    <div class="px-6 py-3 flex items-center focus:text-indigo-500">
                                        {{ log.title }}
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="time_logs.data.length === 0">
                                <td class="border-t px-6 py-4" colspan="6">{{ __('No time log found.') }}</td>
                            </tr>
                            </tbody>
                        </table>
                        <div class="flex w-full px-3 pb-3">
                            <pagination class="mt-1" :links="time_logs.links" />
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
import pickBy from 'lodash/pickBy'
import Pagination from '@/Shared/Pagination'
import mapValues from 'lodash/mapValues'
import throttle from 'lodash/throttle'
import BoardViewMenu from '@/Shared/BoardViewMenu'
import moment from 'moment'
import SearchInput from '@/Shared/SearchInput'
import BoardFilter from "../../Shared/BoardFilter";


export default {
    components: {
        BoardFilter,
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
        project: Object,
        workspace: Object,
        time_logs: Object,
        total_duration: { required: false },
        filters: Object,
    },
    data() {
        return {
            open_filter: false,
            form: {
                search: this.filters.search,
                user: this.filters.user,
                due: this.filters.due,
                label: this.filters.label
            },
        }
    },
    watch: {
        form: {
            deep: true,
            handler: throttle(function() {
                this.$inertia.get(this.route('projects.view.time_logs', this.project.slug || this.project.id), pickBy(this.form), { preserveState: true })
            }, 150),
        },
    },
    computed: {

    },
    created() {
        this.moment = moment
    },
    methods: {
        doFilter(form){
            Object.assign(this.form, form);
        },
        reset() {
            this.form = mapValues(this.form, () => null)
        },
    },
}
</script>
