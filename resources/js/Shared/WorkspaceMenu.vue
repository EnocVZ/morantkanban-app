<template>
  <div v-if="workspace" class="sidebar_wrapper">
      <div class="p-3 flex flex-wrap gap-2 items-center relative">
          <div class="logo flex justify-center items-center w-9 h-9 rounded-full bg-indigo-600 text-lg">
              {{ workspace.name.charAt(0) }}
          </div>
          <Link :href="route('workspace.view', workspace.slug || workspace.id)" class="name flex flex-wrap w-[140px] text-ellipsis leading-5 cursor-pointer">
              {{ workspace.name }}
          </Link>
          <div @click="$emit('enableSidebar')" class="arrow right-2 absolute w-7 h-7 flex items-center hover:bg-[#a6c5e229] justify-center rounded cursor-pointer">
              <icon class="w-4 h-4" name="arrow-left" />
          </div>
      </div>
      <ul class="font-medium text-sm items">
          <li v-if="workspace.member.role === 'admin'">
              <Link :href="route('workspace.tables', workspace.slug || workspace.id)" class="flex items-center px-3 py-2 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-700 group" :class="{'active' : checkActiveClass('component', 'Workspaces_Table') && !($page.props.filters && $page.props.filters.user)}">
                  <icon class="w-4 h-4" name="table" />
                  <span class="ml-3">{{ __('Workspace') }} {{ __('Tasks') }}</span>
              </Link>
          </li>
          <li>
              <Link :href="route('workspace.tables', {'uid': workspace.slug || workspace.id, 'user': $page.props.auth.user.id})" class="flex items-center px-3 py-2 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-700 group" :class="{'active' : checkActiveClass('filter')}">
                  <icon class="w-4 h-4" name="list" />
                  <span class="ml-3">{{ __('My Tasks') }}</span>
              </Link>
          </li>
          <li class="relative" v-if="workspace.member.role === 'admin'">
              <Link class="flex items-center px-3 p-2 group workspace_members" :href="route('workspace.members', workspace.id)" :class="{'active' : checkActiveClass('component', 'Workspaces_Members')}">
                  <icon class="w-4 h-4" name="user" />
                  <span class="flex-1 ml-3 whitespace-nowrap">{{ __('Team Members') }}</span>
                  <button v-if="workspace.member.role === 'admin'" @click="$event.preventDefault();invite_workspace = true" class="flex w-5 h-5 rounded justify-center items-center add__plus">
                      <icon class="w-4 h-4" name="plus" />
                  </button>
              </Link>
          </li>
      </ul>
      <div class="flex cursor-pointer select-none text-[13px] text items-center justify-start gap-3 mt-4 font-bold px-2 pt-2 border-t border-[#ffffff29]" @click="hide_starred=!hide_starred">
          <icon v-if="!hide_starred" name="arrow-down" class="w-4 h-4" />
          <icon v-if="hide_starred" name="arrow-right" class="w-4 h-4" />
          <div class="flex uppercase font-semibold">{{ __('Favorites') }}</div>
      </div>
      <ul class="pt-1 text-sm side_p_list font-medium border-gray-200 dark:border-gray-700 max-h-[calc(100%-350px)] overflow-y-auto" v-show="!hide_starred && favorites.length">
          <li v-for="(project, p_index) in favorites" class="flex group">
              <Link :href="route('projects.view.board', project.slug || project.id)" class="p-2 relative block w-full item" :class="{'active':project.id === ($page.props.project?$page.props.project.id:'')}">
                  <div class="flex h-5 relative">
                      <div v-if="project.background" :style="{ 'background-image' : 'url('+ project.background +')' }" class="flex bg-cover rounded-full w-5 h-5 border"></div>
                      <div class="flex w-full flex-1 justify-center flex-col pl-2 overflow-hidden text-ellipsis whitespace-nowrap">
                          <div class="font-medium text-[13px] leading-[18px]">{{ project.title }}</div>
                      </div>
                      <button class="flex w-7 items-center justify-center" @click="saveProject($event, project)">
                          <icon v-if="!!project.star" name="star" class="w-4 h-4 fill-yellow-500 text-yellow-500 hover:fill-none hover:scale-125" />
                          <icon v-else name="star" class="w-4 h-4 opacity-0 group-hover:opacity-100 hover:text-yellow-500 hover:scale-125" />
                      </button>
                  </div>
              </Link>
          </li>
      </ul>
      <div class="flex text-[13px] text items-center justify-between mt-4 font-bold px-2 pt-2 border-t border-[#ffffff29]">
          <div class="flex justify-start select-none gap-3" @click="hide_projects=!hide_projects">
              <icon v-if="!hide_projects" name="arrow-down" class="w-4 h-4" />
              <icon v-if="hide_projects" name="arrow-right" class="w-4 h-4" />
              <div class="flex cursor-pointer uppercase font-semibold">{{ __('Projects') }}</div>
          </div>
          <div class="flex">
              <Link :href="route('workspace.view', workspace.id)" class="flex w-7 h-7 cursor-pointer rounded justify-center items-center add__plus">
                  <icon class="w-4 h-4" name="project" />
              </Link>
              <div v-if="workspace.member.role === 'admin'" @click="visible.project_create = true" class="flex w-7 h-7 cursor-pointer rounded justify-center items-center add__plus">
                  <icon class="w-4 h-4" name="plus" />
              </div>
          </div>
      </div>
      <create-project v-if="visible.project_create" @create-project="visible.project_create = false" top="30%" left="240px" />
      <invite-workspace-member :workspace="workspace" v-if="invite_workspace" @invite-member="closeInviteMember()" top="100px" left="90px" />
      <ul class="pt-1 text-sm side_p_list font-medium border-gray-200 dark:border-gray-700 max-h-[calc(100%-350px)] overflow-y-auto" v-if="!hide_projects && !loading && projects.length">
          <li v-for="(project, p_index) in projects" class="flex group">
              <Link :href="route('projects.view.board', project.slug || project.id)" class="p-2 relative block w-full item" :class="{'active':project.id === ($page.props.project?$page.props.project.id:'')}">
                  <div class="flex h-5 relative">
                      <div v-if="project.background" :style="{ 'background-image' : 'url('+ project.background.image +')' }" class="flex bg-cover rounded-full w-5 h-5 border"></div>
                      <div class="flex w-full flex-1 justify-center flex-col pl-2 overflow-hidden text-ellipsis whitespace-nowrap">
                          <div class="font-medium text-[13px] leading-[18px]">{{ project.title }}</div>
                      </div>
                      <button class="flex w-7 items-center justify-center" @click="saveProject($event, project)">
                          <icon v-if="!!project.star" name="star" class="w-4 h-4 fill-yellow-500 text-yellow-500 hover:fill-none hover:scale-125" />
                          <icon v-else name="star" class="w-4 h-4 opacity-0 group-hover:opacity-100 hover:text-yellow-500 hover:scale-125" />
                      </button>
                  </div>
              </Link>
          </li>
      </ul>
      <div class="p-3 font-light text-center text-sm" v-if="!loading && !projects.length">{{ 'No project!' }}</div>
  </div>
</template>

<script>
import Icon from '@/Shared/Icon'
import { Link } from '@inertiajs/vue3'
import CreateProject from '@/Shared/Modals/CreateProject'
import InviteWorkspaceMember from "./Modals/InviteWorkspaceMember";
import axios from 'axios'

export default {
    name: 'workspace-menu',
  components: {
      InviteWorkspaceMember,
    Icon,
      Link,
      CreateProject,
  },
    emits: {
        enableSidebar: null,
    },
    data(){
      return{
          projects: [],
          favorites: [],
          workspace: null,
          loading: true,
          hide_projects: false,
          hide_starred: false,
          invite_workspace: false,
          loading_items: [1,2,3,4,5],
          visible: {project_create: false},
          user: null,
          menu_items: [
              {'name': 'Dashboard', 'route': 'dashboard', 'url': 'dashboard', 'icon': 'dashboard'},
              {'name': 'Projects', 'route': 'projects.index', 'url': 'projects', 'icon': 'project'},
          ],
          enable_option : {}
      }
    },
    watch: {
        '$page.props.project': {
            handler() {
                if(this.$page.props.project){
                    if(this.$page.props.project.workspace.id !== this.workspace.id){
                        this.loading = true;
                        this.workspace = this.$page.props.project.workspace
                        this.projects = []
                        this.getProjects()
                    }else{
                        const projectIndex = this.projects.findIndex(p=>p.id === this.$page.props.project.id)
                        this.projects[projectIndex] = this.$page.props.project;
                    }
                    this.getStarredProjects();
                }
            },
            deep: true,
        },
        '$page.props.workspace.id': {
            handler() {
                if(this.$page.props.workspace){
                    this.loading = true;
                    this.workspace = this.$page.props.workspace
                    this.projects = []
                    this.getProjects()
                }
            },
            deep: true,
        },
    },
  methods: {
      checkActiveClass(type, name){
          if(type === 'filter' && this.$page.props.filters && (parseInt(this.$page.props.filters.user, 10) === this.$page.props.auth.user.id)){
              return 'active'
          }else if(type === 'component' && this.$page.component && (this.$page.component.replace('/', '_') === name)){
              return 'active'
          }
      },
      closeInviteMember(){
          this.invite_workspace = false
          window.location.href = this.route('workspace.members',this.workspace.slug || this.workspace.id);
      },
      saveProject(e, project){
          e.preventDefault();
          axios.post(this.route('json.p.starred.save', project.id)).then((resp) => {
              this.getProjects();
              this.getStarredProjects();
          });
      },
      getProjects(){
          axios.get(this.route('json.projects.all', this.workspace.id)).then((response) => {
              if(response.data){
                  this.projects = response.data
              }
              this.loading = false;
          });
      },
      getStarredProjects(){
          axios.get(this.route('json.projects.star', this.workspace.id)).then((response) => {
              if(response.data){
                  this.favorites = response.data.data
              }
              this.loading = false;
          });
      },
  },
    created() {
        this.workspace = this.$page.props.project ? this.$page.props.project.workspace : this.$page.props.workspace
        this.getProjects()
        this.getStarredProjects()
    }
}
</script>
