<template>
  <div class="h-full">

    <Head :title="__(title)" />
    <div class="flex flex-col flex-grow-1 flex-shrink-1 h-full">
      <board-view-menu :project="project" @filter-toggle="open_filter = !open_filter" :filters="filters" view="notes" />
      <div class="p-6 bg-gray-100 min-h-screen">
        <!-- Botón -->
        <div class="mb-4">
          <button class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded shadow"
            @click="newNote = !newNote">
            + Crear nueva nota
          </button>
        </div>

        <!-- Grid de notas -->
        <div class="grid grid-cols-1 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
          <div :class="[note.color, 'text-black p-4 rounded relative shadow']" v-show="newNote">
            <div class="flex justify-end">
              <div class="relative inline-block text-left">
                <!-- Botón que abre el menú -->
                <button class="p-2 rounded hover:bg-gray-200 focus:outline-none" @click="togleMenuNewNote()">⋮</button>
                <!-- Menú de opciones -->


                <!-- Menú principal -->
                <div v-show="note.showMenu" class="absolute mt-2 w-56 bg-white border rounded shadow z-10">

                  <!-- Opción con submenú -->
                  <div class="relative group">
                    <ul>
                      <li v-for="color in colors" :key="color.name"
                        class="flex items-center px-4 py-2 hover:bg-gray-100 cursor-pointer"
                        @click="note.color = color.bg">
                        <span class="w-3 h-3 rounded-full mr-2" :class="color.bg"></span> {{ color.name }}
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>

            <textarea v-model="note.details"
              class="w-full bg-transparent p-2 border-none overflow-hidden focus:outline-none h-48"
              placeholder="Escribe tu nota aquí..." rows="1"></textarea>
            <div class="flex justify-end gap-2 mt-2">
              <loading-button :loading="loadingSave" class="text-sm text-blue-600 " @click="saveNote">Guardar
              </loading-button>

              <button class="text-sm text-red-600 hover:underline" @click="newNote = !newNote">Cancelar</button>
            </div>
          </div>

          <!-- Nota 1 -->
          <div v-for="note in listNote" :key="note.id" :class="[note.color, 'text-black p-4 rounded relative shadow']">
            <div class="flex justify-end">
              <div class="relative inline-block text-left">
                <!-- Botón que abre el menú -->
                <button class="p-2 rounded hover:bg-gray-200 focus:outline-none" @click="toggleMenu(note)">⋮</button>
                <!-- Menú de opciones -->


                <!-- Menú principal -->
                <div v-show="note.showMenu" class="absolute right-0 mt-2 w-56 bg-white border rounded shadow z-10">

                  <!-- Opción con submenú -->
                  <div class="relative group">
                    <button class="flex items-center justify-between w-full px-4 py-2 hover:bg-gray-100">
                      <span>Cambiar color</span>
                      <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                      </svg>
                    </button>

                    <!-- Submenú -->
                    <div
                      class="absolute right-full top-0 mr-1 w-40 bg-white border rounded shadow opacity-0 group-hover:opacity-100 group-hover:block hidden transition-opacity duration-150 z-20">
                      <ul>
                        <li v-for="color in colors" :key="color.name"
                          class="flex items-center px-4 py-2 hover:bg-gray-100 cursor-pointer"
                          @click="changeColor(note, color.bg)">
                          <span class="w-3 h-3 rounded-full mr-2" :class="color.bg"></span> {{ color.name }}
                        </li>
                      </ul>
                    </div>
                  </div>

                  <!-- Otras opciones -->
                  <button class="w-full text-left px-4 py-2 hover:bg-gray-100"
                    @click="enableEditNote(note)">Editar</button>
                  <button class="w-full text-left px-4 py-2 hover:bg-gray-100 text-red-600"
                    @click="openQuestionConfirmDialog(note)">
                    Eliminar</button>
                </div>
              </div>
            </div>


            <div class="whitespace-pre-wrap h-48" v-if="!note.editNote">
              {{ note.details }}
            </div>
            <textarea v-model="note.details"
              class="w-full bg-transparent p-2 border-none overflow-hidden focus:outline-none h-48"
              placeholder="Escribe tu nota aquí..." rows="1" v-else></textarea>

            <div class="flex justify-end gap-2 mt-2" v-if="note.editNote">
              <loading-button :loading="note.loading" class="text-sm text-blue-600 " @click="updateNote(note)">Guardar
              </loading-button>
              <button class="text-sm text-red-600 " @click="note.editNote = false">Cancelar</button>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Modal confirm-->
    <div v-if="openConfirmDialog" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
      <div class="bg-white rounded-lg shadow-lg w-96 p-6">
        <h2 class="text-lg font-semibold mb-4">Confirma que desea realizar la acción</h2>
        <p class="text-sm text-gray-700 mb-6">Esta acción no se puede deshacer.</p>

        <div class="flex justify-end gap-2">
          <loading-button :loading="lodadingDelete" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700"
            @click="confirmDelete">Sí, eliminar</loading-button>
          <button @click="openConfirmDialog = false"
            class="bg-gray-300 text-black px-4 py-2 rounded hover:bg-gray-400">Cancelar</button>
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
