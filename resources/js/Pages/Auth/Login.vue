<template>
    <Head title="Login" />
  <div class="p-6 min-h-screen flex justify-center items-center light">
      <flash-messages />
    <div class="w-full max-w-md">
        <Link :href="route('dashboard')"><logo class="block w-48 mx-auto fill-white" /></Link>
      <form class="mt-8 bg-white dark:bg-slate-900 border border-gray-100 rounded-lg shadow-xl overflow-hidden" @submit.prevent="login">
        <div class="px-10 py-6">
          <text-input v-model="form.email" :error="form.errors.email" class="mt-10" label="Email" type="email" autofocus autocapitalize="off" />
          <text-input v-model="form.password" :error="form.errors.password" class="mt-6" label="Password" type="password" />
          <label class="mt-4 select-none flex items-center" for="remember">
            <input id="remember" v-model="form.remember" class="mr-1" type="checkbox" />
            <span class="text-sm">Remember Me</span>
          </label>
            <loading-button :loading="form.processing" class="ml-auto btn-indigo w-full items-center justify-center mt-6" type="submit">{{ __('Login') }}</loading-button>
            <div class="mt-5 flex justify-center"><Link class="ml-2 " :href="route('password.reset')">{{ __('Forgot your password?') }}</Link></div>
            <div v-if="enable_register" class="mt-5 flex justify-center">Don’t have account? <Link class="ml-2 " :href="route('register')">{{ __('Register') }}</Link></div>
        </div>
          <div class=" py-4 bg-gray-100 dark:bg-slate-900 border-t border-gray-100 dark:border-gray-700 flex flex-col gap-1 login-as items-center" v-if="is_demo">
              <h2 class="text-sm font-semibold mb-3">Click one of the following buttons to login automatically. </h2>
              <div class="action flex flex-col sm:flex-row gap-3">
                  <button class=" btn-indigo" @click="autofillLogin($event, 'admin')">Login as Admin</button>
                  <button class=" btn-indigo" @click="autofillLogin($event,'normal')">Login as Normal User</button>
              </div>
          </div>
      </form>
    </div>
  </div>
</template>

<script>
import Logo from '@/Shared/Logo'
import TextInput from '@/Shared/TextInput'
import LoadingButton from '@/Shared/LoadingButton'
import { Head, Link } from '@inertiajs/vue3'
import FlashMessages from '@/Shared/FlashMessages'

export default {
  metaInfo: { title: 'Login' },
  components: {
      FlashMessages,
    LoadingButton,
    Logo,
    TextInput,
      Head,
      Link,
  },
    props: {
        is_demo: Number,
        enable_registration: Object
    },
  data() {
    return {
        enable_register: parseInt(this.enable_registration.value, 10),
      form: this.$inertia.form({
        email: '',
        password: '',
        remember: false,
      }),
    }
  },
  methods: {
      login() {
          this.form.post(this.route('login.store'))
      },
      autofillLogin(e, role){
          e.preventDefault()
          const roleEmails = { 'admin': 'john.due.helo@mail.com', 'normal': 'sabbir@example.com'}
          this.form.email = roleEmails[role]
          this.form.password = 'secret'
          this.login();
      }
  }
}
</script>
