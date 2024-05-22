<template>
    <div class="h-full">
        <Head :title="__(title)" />
        <div class="flex flex-col flex-grow-1 flex-shrink-1 h-full">
            <board-view-menu :project="project" @filter-toggle="open_filter = !open_filter" :filters="filters" view="calendar" />
            <div class="flex flex-col task__table overflow-y-auto h-full">
                <div class="inline-block min-w-full h-full py-4 align-middle md:px-3 lg:px-4">
                    <div v-if="calendarView" id="ec_module" class="calendar-container">
                        <Qalendar
                            :events="calendarEvents"
                            :selected-date="getRequestDate()"
                            :config="evConfig"
                            @updatedPeriod="handleDateUpdate"
                            @updatedMode="handleModeUpdate"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import Layout from '@/Shared/Layout'
import {Head, Link} from '@inertiajs/vue3'
import moment_timezone from 'moment-timezone'
import Datepicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import { ref } from 'vue'

import BoardViewMenu from '@/Shared/BoardViewMenu'
import {Qalendar} from 'qalendar'
import throttle from 'lodash/throttle'
import pickBy from 'lodash/pickBy'

export default {
    metaInfo: { title: 'FAQs' },
    components: {
        Link,
        Head,
        Datepicker,
        Qalendar,
        BoardViewMenu,
    },
    layout: Layout,
    props: {
        auth: Object,
        title: String,
        tasks: Object,
        filters: Object,
        project: Object,
        list_index: Object,
        board_lists: Object,
        lists: {
            required: false,
        },
    },
    watch: {
        form: {
            deep: true,
            handler: throttle(function() {
                this.$inertia.get(this.route('projects.view.calendar', this.project.slug || this.project.id), pickBy(this.form), { preserveState: true })
            }, 150),
        },
    },
    remember: 'form',
    setup() {
        let dateOverride = ref(null);
        const closeOverrideCalendar = () => {
            if (!!dateOverride.value && dateOverride.value.length) {
                dateOverride.value[0].closeMenu()
            }
        }
        return {
            dateOverride,
            closeOverrideCalendar
        }
    },
    data() {
        return {
            calendarView: false,
            open_filter: false,
            calendarEvents: [],
            evConfig: {
                style: {
                    colorSchemes: {},
                },
                week: {
                    // Takes the value 'sunday' or 'monday'
                    // However, if startsOn is set to 'sunday' and nDays to 5, the week displayed will be Monday - Friday
                    startsOn: 'monday',
                    // Takes the values 5 or 7.
                    nDays: 7,
                    // Scroll to a certain hour on mounting a week. Takes any value from 0 to 23.
                    // This option is not compatible with the 'dayBoundaries'-option, and will simply be ignored if custom day boundaries are set.
                    scrollToHour: 5,
                    weekHeight: '3400px',
                },
                month: {
                    // Hide leading and trailing dates in the month view (defaults to true when not set)
                    showTrailingAndLeadingDates: false,
                },
                // defaultMode: this.filters.view || 'week',
                defaultMode: 'week',
                // The silent flag can be added, to disable the development warnings. This will also bring a slight performance boost
                isSilent: true,
                showCurrentTime: true, // Display a line indicating the current time
            },
            form: {
                search: this.filters.search,
                trashed: this.filters.trashed,
                view: ['calendar'].includes(this.filters.period) ? this.filters.view : null,
                range: ['range', 'calendar'].includes(this.filters.period) ? this.filters.range : null,
            },
            event_dates: {},
            rangeDate: null
        }
    },
    methods: {
        crossEvent(id){
            const findCrossEventIndex = this.events.data.findIndex(e=> e.id = id)
            console.log(findCrossEventIndex)
            this.events.data[findCrossEventIndex].is_active = false
        },
        getRequestDate(){
            return new Date()
        },
        selectRange(){
            console.log(this.rangeDate)
            this.form.range = {start: this.rangeDate[0], end: this.rangeDate[1]}
            this.form.period = 'range'
        },
        activeTab(index){
            for (const tab_item of this.tabs) {
                tab_item.active = false
            }
            if(this.tabs[index].period === 'range'){

            }else{
                this.form.period = this.tabs[index].period
            }
        },
        filter(){

        },
        handleDateUpdate(obj){
            this.form.range = {start: this.moment(obj.start).format('YYYY-MM-DD'), end: this.moment(obj.end).format('YYYY-MM-DD')}
            this.form.period = 'calendar'
            this.form.view = this.filters.view || 'week'
        },
        handleModeUpdate(obj){
            this.form.range = {start: this.moment(obj.period.start).format('YYYY-MM-DD'), end: this.moment(obj.period.end).format('YYYY-MM-DD')}
            this.form.view = obj.mode
        },
        processData(){
            this.event_dates = {}
            for (const event_item of this.tasks) {
                console.log(event_item);
                // if(event_item.color){
                //     this.evConfig.style.colorSchemes[event_item.uid] = {
                //         'color': '#ffffff',
                //         'backgroundColor': event_item.color
                //     }
                // }
                const created_at = this.moment(event_item.created_at || event_item.updated_at, 'YYYY-MM-DD HH:mm:ss').format('YYYY-MM-DD HH:mm');
                const due_date = event_item.due_date? this.moment(event_item.due_date, 'YYYY-MM-DD HH:mm:ss').format('YYYY-MM-DD HH:mm'): '';
                let html = '';
                html+= '<span><a href="/p/board/'+event_item.project_id+'?task='+event_item.id+'">'+event_item.title+'</a></span>';
                html+= '<span><strong>Board:</strong> <a href="/p/board/'+event_item.project_idet +'">'+(event_item.list?event_item.list.title:'')+'</a></span>';
                html+= '<span><strong>Created At:</strong> '+this.moment(event_item.created_at || event_item.updated_at, 'YYYY-MM-DD HH:mm:ss').format('Do MMMM, YYYY')+'</span>';
                if(due_date){
                    html+= '<span><strong>Due Date:</strong> '+this.moment(event_item.due_date, 'YYYY-MM-DD HH:mm:ss').format('Do MMMM, YYYY')+'</span>';
                }
                this.calendarEvents.push({
                    title: (event_item.list?event_item.list.title+' : ':'')+event_item.title,
                    time: {start: created_at, end: due_date || created_at},
                    description: html,
                    // time: { start: this.moment(event_item.date+' '+event_item.start, 'YYYY-MM-DD h:mma').format('YYYY-MM-DD HH:mm'), end: this.moment(event_item.date+' '+event_item.end, 'YYYY-MM-DD h:mma').format('YYYY-MM-DD HH:mm') },
                    // colorScheme: event_item.color?event_item.uid: null,
                    id: event_item.id,
                })
            }
            this.calendarView = true
        }
    },
    mounted() {
        this.processData()
    },
    created() {
        this.moment = moment_timezone
    }
}
</script>
