<template>
  <div class="sec-cont">
    <Head :title="title" />
    <div class="bg-white rounded-md shadow overflow-hidden mr-2">
        <form @submit.prevent="update">
        <div class="p-8 -mr-6 -mb-8 flex flex-wrap">
            <text-input v-model="form.app_name" :error="form.errors.app_name" class="pr-6 pb-8 w-full lg:w-1/3" :label="__('App Name')" />
            <select-input v-model="form.default_language" :error="form.errors.default_language" class="pr-6 pb-8 w-full lg:w-1/3" :label="__('Default Language')">
                <option v-for="l in languages" :key="l.id" :value="l.code">{{ l.name }}</option>
            </select-input>
            <div class="pb-8 pr-6 w-full flex lg:w-1/3">
                <file-input v-model="form.favicon" :error="form.errors.favicon" class="pr-2 w-full lg:w-3/5" type="file" accept="image/png" label="Favicon" />
                <div class="w-full lg:w-2/5 flex items-end justify-start">
                    <img v-if="form.main_favicon" class="block w-auto h-[30px]" :src="form.main_favicon" />
                </div>
            </div>
            <div class="pb-8 pr-6 w-full flex lg:w-1/3">
                <file-input v-model="form.logo" :error="form.errors.logo" class="pr-2 w-full lg:w-3/5" type="file" accept="image/png" label="Logo" />
                <div class="w-full lg:w-2/5 flex items-end justify-start">
                    <img v-if="form.main_logo" class="block w-auto h-[30px] rounded" :src="form.main_logo" />
                </div>
            </div>
            <div class="pb-8 pr-6 w-full flex lg:w-1/3">
                <file-input v-model="form.logo_white" :error="form.errors.logo_white" class="pr-2 w-full lg:w-3/5" type="file" accept="image/png" label="Logo White" />
                <div class="w-full lg:w-2/5 flex items-end justify-start">
                    <img v-if="form.main_logo_white" class="block w-auto h-[30px] rounded bg-dark" :src="form.main_logo_white" />
                </div>
            </div>
            <div class="flex justify-start pb-8 w-full md:w-1/2 flex-col md:flex-row">
                <div class="font-bold text-sm mb-1 pr-6">{{ __('Enable Registration') }} </div>
                <div class="flex items-center mb-4">
                    <input id="enableRegistration" type="checkbox" v-model="form.enable_registration" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600">
                    <label for="enableRegistration" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{ __('Show Registration link on the login page') }}</label>
                </div>
            </div>
            <div class="assigned_user pr-6 pb-8 w-full lg:w-full flex flex-col">
                <div class="font-bold text-sm mb-1">{{ __('Email Notifications') }} </div>
            </div>
            <div class="flex justify-start pr-6 pb-8 w-full md:w-1/2 flex-col md:flex-row" v-for="(notification, ni) in form.email_notifications" :key="ni">
                <label :for="notification.slug" class="flex toggle_swtich items-center cursor-pointer">
                    <div class="relative">
                        <input :id="notification.slug" type="checkbox" class="sr-only" v-model="notification.value" />
                        <div class="w-10 h-4 bg-gray-400 rounded-full shadow-inner"></div>
                        <div class="dot absolute w-6 h-6 bg-white rounded-full shadow -left-1 -top-1 transition"></div>
                    </div>
                    <div class="ml-3 text-sm">
                        {{ notification.name }}
                    </div>
                </label>
            </div>
            <div class="assigned_user pr-6 pb-8 w-full lg:w-full flex flex-col">
                <div class="font-bold text-sm mb-1">{{ __('Cron Job Instruction') }} </div>
                <div class="prose dark:prose-invert w-full">
                    <p class="mt-2 font-light">If you would like to send mail without delaying you can set a cron job for that.
                        To do that firstly you need to enable Queue with add <code>QUEUE_ENABLE=true</code> on the .env file. After that add a cronjob task as following.</p>
                    <code class="mt-5 mb-5 prose block whitespace-pre mt-1 text-sm">*/3 * * * * /usr/bin/php artisan queue:work --queue=high,default --stop-when-empty</code>
                    <small>On the above cron example, the cron will run every three(3) minutes to send mail based on Queue.</small>

                    <p class="mt-3 font-light">For the same thing if you would like to do on the cPanel or other shared hosting panel server you can add as like following</p>
                    <code class="mt-5 mb-5 prose block whitespace-pre mt-1 text-sm">*/3	* *	* *	wget -q -O - https://website.com/cron/queue_work >/dev/null 2>&1</code>
                </div>
            </div>

            <textarea-input v-model="form.custom_css" :error="form.errors.custom_css" :rows="15" class=" pb-6 w-full" placeholder="your custom css here.." :label="__('Custom CSS')"></textarea-input>

        </div>
            <div class="px-8 py-4 bg-gray-50 border-t border-gray-100 flex items-center">
                <loading-button :loading="form.processing" class="btn-indigo ml-auto" type="submit">{{ __('Save') }}</loading-button>
            </div>
        </form>

    </div>
  </div>
</template>

<script>
import { Link, Head } from '@inertiajs/vue3'
import Icon from '@/Shared/Icon'
import Layout from '@/Shared/Layout'
import Pagination from '@/Shared/Pagination'
import TextInput from '@/Shared/TextInput'
import TextareaInput from '@/Shared/TextareaInput'
import SelectInput from '@/Shared/SelectInput'
import LoadingButton from '@/Shared/LoadingButton'
import FileInput from '@/Shared/FileInput'

export default {
  metaInfo: { title: 'Priorities' },
  components: {
    Icon,
    Link,
      Head,
      FileInput,
    Pagination,
      TextInput,
      TextareaInput,
      SelectInput,
      LoadingButton,
  },
  layout: Layout,
  props: {
      title: String,
      settings: Object,
      languages: Array,
      pusher: Boolean,
  },
    remember: 'form',
  data() {
    return {
        form: this.$inertia.form({
            app_name: this.settings.app_name.value,
            enable_registration: Boolean(parseInt(this.settings.enable_registration.value, 10)),
            logo: null,
            logo_white: null,
            favicon: null,
            main_logo: '/images/logo.png',
            main_logo_white: '/images/logo_white.png',
            main_favicon: '/favicon.png',
            default_language: this.settings.default_language.value,
            custom_css: typeof this.settings.custom_css !== 'undefined' && this.settings.custom_css ? this.settings.custom_css.value : null,
            email_notifications: this.settings.email_notifications.value.map(en=>{return {'name': en.name,'slug': en.slug,'value': !!en.value}}),
        }),
    }
  },
    created() {
      console.log(this.form)
    },
    methods: {
      update() {
          const vm = this;
          this.form.post(this.route('global.update'), {
              onSuccess: () => {
                  const successMessage = vm.$page.props.flash.success
                  this.form.logo = null
                  this.form.logo_white = null
                  if(successMessage){
                      window.location.reload()
                  }
              }
          })
      },
  },
}
</script>
