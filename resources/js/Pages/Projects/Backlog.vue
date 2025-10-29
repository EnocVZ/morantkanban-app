<template>
    <div class="h-full">

        <Head :title="__(title)" />
        <div class="flex flex-col flex-grow-1 flex-shrink-1 h-full">
            <board-view-menu :project="project" @filter-toggle="open_filter = !open_filter" :filters="filters"
                view="backlog" />
            <div class="p-6 bg-gray-100 min-h-screen">
                <div class="table__view">
                    <table class="table-auto">
                        <thead>
                            <tr>
                                <th>Song</th>
                                <th>Artist</th>
                                <th>Year</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="list-group-item group">
                                <td
                                    class="px-2 py-2 text-sm font-medium whitespace-nowrap w-[calc(32%-70px)] hover:bg-gray-100">
                                    The Sliding Mr. Bones (Next Stop, Pottersville)</td>
                                <td>Malcolm Lockyer</td>
                                <td>1961</td>
                            </tr>
                            <tr>
                                <td>Witchy Woman</td>
                                <td>The Eagles</td>
                                <td>1972</td>
                            </tr>
                            <tr>
                                <td>Shining Star</td>
                                <td>Earth, Wind, and Fire</td>
                                <td>1975</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>



    </div>
</template>
<script>
import Layout from '@/Shared/Layout'
import { Head, Link } from '@inertiajs/vue3'
import '@vuepic/vue-datepicker/dist/main.css'

import BoardViewMenu from '@/Shared/BoardViewMenu'
import LoadingButton from '@/Shared/LoadingButton'
import axios from 'axios'

export default {
    metaInfo: { title: 'FAQs' },
    components: {
        Link,
        Head,
        BoardViewMenu,
        LoadingButton
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
        notes: {
            required: true,
        },
    },
    watch: {
    },

    data() {
        return {
            calendarView: false,
            open_filter: false,
            newNote: false,
            openConfirmDialog: false,
            loadingSave: false,
            lodadingDelete: false,
            calendarEvents: [],
            listNote: [],
            colors: [
                { name: "Amarillo", bg: "bg-yellow-100" },
                { name: "Verde", bg: "bg-green-300" },
                { name: "Rosa", bg: "bg-pink-300" },
                { name: "Púrpura", bg: "bg-purple-300" },
                { name: "Azul", bg: "bg-blue-300" },
                { name: "Gris", bg: "bg-gray-400" },
            ],

            form: {
                search: this.filters.search,
                trashed: this.filters.trashed,
                view: ['calendar'].includes(this.filters.period) ? this.filters.view : null,
                range: ['range', 'calendar'].includes(this.filters.period) ? this.filters.range : null,
            },
            event_dates: {},
            rangeDate: null,
            note: {
                details: '',
                color: 'bg-yellow-100',
            },
            noteToDelete: {},
        }
    },
    methods: {
        togleMenuNewNote() {
            this.note.showMenu = !this.note.showMenu;
        },



        toggleMenu(note) {
            this.listNote.forEach((n) => {
                if (n.id !== note.id) n.showMenu = false;
            });
            note.showColors = false;
            note.showMenu = !note.showMenu;

        },

        enableEditNote(note) {
            this.listNote.forEach((n) => {
                if (n.id !== note.id) n.editNote = false;
            });
            note.showMenu = false;
            note.editNote = true;
        },

        changeColor(note, bgClass) {
            note.color = bgClass;
        },

        saveNote() {
            const REQUEST = {
                details: this.note.details,
                color: this.note.color,
                project_id: this.project.id,
            };
            this.loadingSave = true;
            axios.post(this.route('notes.new'), REQUEST).then((response) => {
                if (!response.data.error) {
                    const newId = response.data.data.id;
                    this.listNote.unshift({ ...this.note, id: newId, showMenu: false });
                    this.note.details = '';
                    this.newNote = false;
                }
            }).finally(() => {
                this.loadingSave = false;
            });

        },
        updateNote(note) {
            const REQUEST = {
                details: note.details,
                color: note.color
            };
            note.loading = true;
            axios.post(this.route('notes.update', note.id), REQUEST).finally(() => {
                note.editNote = false;
                note.loading = false;
            });
        },

        confirmDelete() {
            this.lodadingDelete = true
            axios.delete(this.route('notes.delete', this.noteToDelete.id)).then(() => {
                this.listNote = this.listNote.filter(note => note.id !== this.noteToDelete.id);
                this.openConfirmDialog = false;
            }).finally(() => {
                this.lodadingDelete = false
            });
        },
        openQuestionConfirmDialog(note) {
            note.showMenu = false;
            this.openConfirmDialog = true;
            this.noteToDelete = note;
        },
    },
    mounted() {
        //  this.processData()
    },
    created() {
        this.listNote = this.notes;
    }
}
</script>
