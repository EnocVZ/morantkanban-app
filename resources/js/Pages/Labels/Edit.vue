<template>
  <div class="sec-cont">
      <Head :title="__(title)" />
    <div class="bg-white rounded-md shadow overflow-hidden max-w-3xl">
      <form @submit.prevent="update">
          <div class="p-8 -mb-6 flex ">
              <text-input v-model="form.name" :error="form.errors.name" class="pr-6 pb-8 w-full lg:w-1/2" :label="__('Name')" />
          </div>
        <div class="px-8 py-4 bg-gray-50 border-t border-gray-100 flex items-center">
          <button class="text-red-600 hover:underline" tabindex="-1" type="button" @click="destroy">
            {{ __('Delete') }} {{ __('Label') }}</button>
          <loading-button :loading="form.processing" class="btn-indigo ml-auto" type="submit">{{ __('Update') }} {{ __('Label') }}</loading-button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import Layout from '@/Shared/Layout'
import { Link, Head } from '@inertiajs/vue3'
import TextInput from '@/Shared/TextInput'
import LoadingButton from '@/Shared/LoadingButton'

export default {
  metaInfo() {
    return { title: this.form.name }
  },
  components: {
    LoadingButton,
    TextInput,
    Link,
      Head,
  },
  layout: Layout,
  props: {
    title: String,
    label: Object,
  },
  remember: 'form',
  data() {
    return {
      form: this.$inertia.form({
          name: this.label.name,
          color: this.label.color,
      }),
    }
  },
  methods: {
    update() {
      this.form.put(this.route('labels.update', this.label.id))
    },
    destroy() {
      if (confirm('Are you sure you want to delete this label?')) {
        this.$inertia.delete(this.route('labels.destroy', this.label.id))
      }
    },
    restore() {
      if (confirm('Are you sure you want to restore this label?')) {
        this.$inertia.put(this.route('labels.restore', this.label.id))
      }
    },
  },
}
</script>
