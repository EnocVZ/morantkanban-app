<template>
  <div class="sec-cont">
    <Head :title="__(title)" />
    <div class="mb-6 flex justify-between items-center">
        <search-input v-model="form.search" class="w-full max-w-md mr-4" @reset="reset"></search-input>
      <Link class="btn-indigo" :href="this.route('roles.create')">
        <span>{{ __('Create a New Role') }}</span>
      </Link>
    </div>
    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <tr class="text-left font-bold">
          <th class="px-6 pt-6 pb-4">#{{ __('ID') }}</th>
          <th class="px-6 pt-6 pb-4">{{ __('Name') }}</th>
          <th class="px-6 pt-6 pb-4">{{ __('Slug') }}</th>
          <th class="px-6 pt-6 pb-4">{{ __('Create Workspace') }}</th>
          <th class="px-6 pt-6 pb-4">{{ __('Create Project') }}</th>
        </tr>
        <tr v-for="role in roles.data" :key="role.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
            <td class="border-t">
                <Link class="px-6 py-4 flex items-center focus:text-indigo-500" :href="this.route('roles.edit', role.id)">
                    {{ role.id }}
                </Link>
            </td>
          <td class="border-t">
            <Link class="px-6 py-4 flex items-center focus:text-indigo-500" :href="this.route('roles.edit', role.id)">
              {{ role.name }}
            </Link>
          </td>
            <td class="border-t">
                <Link class="px-6 py-4 flex items-center focus:text-indigo-500" :href="this.route('roles.edit', role.id)">
                    {{ role.slug }}
                </Link>
            </td>
            <td class="border-t">
                <Link class="px-6 py-4 flex items-center focus:text-indigo-500" :href="this.route('roles.edit', role.id)">
                    {{ !!role.create_workspace }}
                </Link>
            </td>
            <td class="border-t">
                <Link class="px-6 py-4 flex items-center focus:text-indigo-500" :href="this.route('roles.edit', role.id)">
                    {{ !!role.create_project }}
                </Link>
            </td>
          <td class="border-t w-px" @click="openModal(role.id)" style="cursor: pointer;">
          <span class="font-semibold hover:text-blue-600 rounded text-blue-700">Permisos</span>
        </td>
          <td class="border-t w-px">
            <Link class="px-4 flex items-center" :href="this.route('roles.edit', role.id)" tabindex="-1">
              <icon name="cheveron-right" class="block w-6 h-6 fill-gray-400" />
            </Link>
          </td>
        </tr>
        <tr v-if="roles.data.length === 0">
          <td class="border-t px-6 py-4" colspan="4">No se encontraron roles</td>
        </tr>
      </table>
    </div>
    <pagination class="mt-6" :links="roles.links" />
  </div>
  <div v-if="showModalRole" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" >
    <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl mx-4 relative">
      <!-- Header -->
      <div class="flex justify-between items-center border-b p-4">
        <h2 class="text-lg font-semibold">Permisos para el rol</h2>
        <button
          @click="showModalRole = false"
          class="text-gray-400 hover:text-white text-xl leading-none"
        >
          ✕
        </button>
      </div>

      <!-- Contenido -->
      <div class="p-4 overflow-y-auto max-h-[70vh]">
        <permission :roleId="roleId" @onSelectPermission="onSelectPermission"/>
      </div>

      <!-- Footer -->
      <div class="flex justify-end border-t p-4">
        <button
          @click="showModalRole = false"
          class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"
        >
          Cerrar
        </button>
        <loading-button :loading="loadingSavePermission" @click="saveSelection"
          class="ml-2 px-4 py-2 text-white bg-indigo-600 rounded hover:bg-indigo-700">Guardar cambios</loading-button>
        
      </div>
    </div>
  </div>
</template>

<script>
import { Link, Head } from '@inertiajs/vue3'
import Icon from '@/Shared/Icon'
import pickBy from 'lodash/pickBy'
import Layout from '@/Shared/Layout'
import throttle from 'lodash/throttle'
import mapValues from 'lodash/mapValues'
import Pagination from '@/Shared/Pagination'
import SearchInput from '@/Shared/SearchInput'
import Permission from '@/Pages/Roles/Permission.vue'
import LoadingButton from '@/Shared/LoadingButton'
import axios from 'axios'

export default {
  metaInfo: { title: 'Roles' },
  components: {
    Icon,
    Link,
    Head,
    Pagination,
    SearchInput,
    Permission,
    LoadingButton
  },
  layout: Layout,
  props: {
      title: String,
    filters: Object,
    roles: Object,
  },
  data() {
    return {
      showModalRole: false,
      form: {
        search: this.filters.search,
      },
      roleId:0,
      permissionSelected: [],
      loadingSavePermission: false
    }
  },
  watch: {
    form: {
      deep: true,
      handler: throttle(function() {
        this.$inertia.get(this.route('roles'), pickBy(this.form), { preserveState: true })
      }, 150),
    },
  },
  methods: {
    openModal(roleId){
        this.roleId = roleId;
        this.showModalRole = true;
    },
    onSelectPermission(data){
      this.permissionSelected = data;
    },
    reset() {
      this.form = mapValues(this.form, () => null)
    },
    saveSelection(){
      this.loadingSavePermission = true;
      const request = {data: this.permissionSelected};
      axios.post(this.route('permission.assign'), request).then(response =>{
   
      }).finally(()=>{
          this.loadingSavePermission = false;
          this.showModalRole = false;
      });
    }
  },
}
</script>
