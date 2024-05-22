<template>
  <div>
    <div class="menu-item" v-for="(menu_item, m_index) in menu_items" :key="m_index"
         :class="isUrl(menu_item.url) ? ' active' : ''" v-on="menu_item.submenu?{click: (e) => addActiveClass(e)}:{}">
      <Link class="flex items-center group py-3 menu-link" :href="menu_item.route?route(menu_item.route):'#'" :class="{'have-sub-menu': menu_item.submenu}">
        <icon :name="menu_item.icon" class="w-6 h-6 mr-3 rtl:ml-3 menu__icon" />
        <div class="menu__name">{{ __(menu_item.name) }}</div>
      </Link>
        <div v-if="menu_item.submenu" class="sub-menu-items">
            <Link class="sub-menu-item" v-for="(sub_menu_item, s_m_index) in menu_item.submenu" :key="s_m_index"
                :class="this.isUrl(sub_menu_item.url) ? ' active' : ''" :href="sub_menu_item.param?route(sub_menu_item.route,sub_menu_item.param):route(sub_menu_item.route)">
                <icon v-if="sub_menu_item.icon" :name="sub_menu_item.icon" class="w-4 h-4 mr-1 rtl:ml-1 menu__icon" />
                <icon v-else name="dash" class="w-4 h-4 mr-1 rtl:mr-1 menu__icon" />
                <div class="menu__name">{{ __(sub_menu_item.name) }}</div>
            </Link>
        </div>
    </div>
  </div>
</template>

<script>
import Icon from '@/Shared/Icon'
import { Link } from '@inertiajs/vue3'

export default {
  components: {
    Icon,
      Link,
  },
    data(){
      return{
          user: null,
          menu_items: [
              {'name': 'Global', 'route': 'global', 'url': 'settings/global', 'icon': 'global_setting'},
              {'name': 'Manage Users', 'route': 'users', 'url': 'settings/users', 'icon': 'users'},
              {'name': 'User Roles', 'route': 'roles', 'url': 'settings/roles', 'icon': 'user_role'},
              {'name': 'Languages', 'route': 'languages', 'url': 'settings/languages', 'icon': 'edit'},
              {'name': 'Email Templates', 'route': 'templates', 'url': 'settings/templates', 'icon': 'email'},
              {'name': 'SMTP Mail', 'route': 'settings.smtp', 'url': 'settings/smtp', 'icon': 'email_template'}
          ],
          enable_option : {}
      }
    },
  methods: {
    isUrl(...urls) {
      let currentUrl = this.$page.url.substr(1)
        currentUrl = currentUrl.replace('dashboard/','')
      if (urls[0] === '') {
        return currentUrl === ''
      }
      return urls.filter(url => currentUrl.startsWith(url)).length
    },
      addActiveClass(e){
          e.currentTarget.classList.toggle('hover')
      }
  },
    created() {
      this.user = this.$page.props.auth.user;
    }
}
</script>
