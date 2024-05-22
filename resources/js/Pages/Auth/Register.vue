<template>
    <Head title="Register" />
  <div class="p-6 min-h-screen flex justify-center items-center light">
    <div class="w-full max-w-xl	">
        <Link :href="route('dashboard')"><logo class="block w-48 mx-auto fill-white" /></Link>
      <form class="mt-8 bg-white dark:bg-slate-900 border border-gray-100 rounded-lg shadow-xl overflow-hidden" @submit.prevent="login">
        <div class="px-10 py-12">
          <h2 class="text-center font-bold text-xl">{{ __('Registration') }}</h2>
          <div class="mx-auto mt-2 mb-6 w-24 border-b" />
            <flash-messages />
            <div class="flex flex-wrap -mb-8 -mr-6 p-3">
                <text-input v-model="form.first_name" :error="form.errors.first_name" class="pb-8 pr-6 w-full lg:w-1/2" label="First name" type="text" autofocus autocapitalize="off" :is_required="true" required />
                <text-input v-model="form.last_name" :error="form.errors.last_name" class="pb-8 pr-6 w-full lg:w-1/2" label="Last name" type="text" autofocus autocapitalize="off" :is_required="true" required />
                <text-input v-model="form.email" :error="form.errors.email" class="pb-8 pr-6 w-full lg:w-1/2" label="Email" type="email" autofocus autocapitalize="off" :is_required="true" required />
                <text-input v-model="form.phone" :error="form.errors.phone" class="pb-8 pr-6 w-full lg:w-1/2" label="Phone" type="text" autofocus autocapitalize="off" />
                <text-input v-model="form.address" :error="form.errors.address" class="pb-8 pr-6 w-full" label="Address" type="text" autofocus autocapitalize="off" />
                <text-input v-model="form.password" :error="form.errors.password" class="pb-8 pr-6 w-full lg:w-1/2" label="Password" type="password" :is_required="true" required />
                <text-input v-model="form.confirm_password" :error="form.errors.confirm_password" class="pb-8 pr-6 w-full lg:w-1/2" label="Confirm Password" type="password" :is_required="true" required />
                <loading-button :loading="form.processing" class="ml-auto btn-indigo w-full items-center justify-center" type="submit">Submit</loading-button>
            </div>
            <div class="mt-8 flex justify-center">Already have an account? <Link class="ml-2" :href="route('login')">Login</Link></div>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import Logo from '@/Shared/Logo'
import TextInput from '@/Shared/TextInput'
import LoadingButton from '@/Shared/LoadingButton'
import FlashMessages from '@/Shared/FlashMessages'
import { Head, Link } from '@inertiajs/vue3'

export default {
  metaInfo: { title: 'Login' },
  components: {
    LoadingButton,
    Logo,
    TextInput,
      Head,
      Link,
      FlashMessages,
  },
    props: {
        is_demo: Number
    },
  data() {
    return {
      form: this.$inertia.form({
        first_name: '',
        last_name: '',
          email: '',
          phone: '',
        address: '',
        password: '',
        confirm_password: '',
      }),
    }
  },
  methods: {
      login() {
          if(this.form.password !== this.form.confirm_password){
              alert('Your password is not matched correctly.')
              return
          }
          this.form.post(this.route('register.store'))
      },
      autofillLogin(e, role){
          e.preventDefault()
          const roleEmails = { 'admin': 'john.due.helo@mail.com', 'manager': 'robert.slaughter@mail.com', 'customer' : 'mmarks@example.com'}
          this.form.email = roleEmails[role]
          this.form.password = 'secret'
          this.login();
      }
  }
}
</script>
