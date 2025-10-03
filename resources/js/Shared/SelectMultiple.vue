<template>
  <div class="flex items-center px-2">
    <!-- Título -->
    <slot name="title"/>
    <!-- Botón abrir -->
    <div class="relative ml-auto">
      <span class="cursor-pointer" @click="showAssigneeBox = true" :alt="title">
        <Icon class="h-4 w-4 hover:opacity-80" name="add" />
      </span>

      <!-- Dropdown -->
      <div
        v-if="showAssigneeBox"
        class="absolute right-1 flex w-[300px] z-10 text-sm flex-col bg-white px-4 py-4 rounded shadow"
      >
        <!-- Header -->
        <h4 class="text-center mb-3 font-bold">{{ title }}</h4>
        <div
          class="absolute cursor-pointer hover:bg-gray-200 top-3 right-3 p-1.5 rounded"
          @click="showAssigneeBox = false"
        >
          <Icon class="w-4 h-4" name="close" />
        </div>

        <!-- Input búsqueda -->
        <input
          v-model="user_search"
          class="border-[2px] px-2 py-1 border-gray-400 rounded-[3px] w-full"
          placeholder="Buscar usuario"
        />

        <!-- Lista usuarios -->
        <ul class="flex flex-col mt-3 gap-1 h-48 max-h-48 overflow-y-auto">
          <li v-for="(userObject, index) in filteredUsers" :key="userObject[keyName]">
            <label
              :for="'td_u_id_' + index"
              class="flex p-2 cursor-pointer hover:bg-gray-200 rounded"
            >
              <!-- Checkbox -->
              <input
                :id="'td_u_id_' + index"
                class="w-5 ml-1 mr-2"
                type="checkbox"
                :checked="selected.includes(userObject[keyName])"
                @change="toggleUser(userObject[keyName], $event.target.checked)"
              />

              <!-- Avatar -->
              <img
                v-if="userObject?.user?.photo_path"
                :alt="userObject.user[label]"
                class="w-6 h-6 rounded-full"
                :src="userObject.user.photo_path"
              />
              <img
                v-else
                :alt="userObject.user[label]"
                class="w-6 h-6 rounded-full"
                src="/images/user.svg"
              />
              <!-- Nombre -->
              <span class="p-1">{{ userObject.user[label] }}</span>
            </label>
          </li>

          <!-- No results -->
          <li v-if="filteredUsers.length === 0" class="text-gray-400 text-sm p-2">
            No se encontraron usuarios
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script>
import Icon from '@/Shared/Icon'
export default {
  components: { Icon },
  name: "SelectMultiple",
  props: {
    title: {
      type: String,
      default: "Assignees",
    },
    users: {
      type: Array,
      required: true, // [{ user_id: 1, user: { name, photo_path }}]
    },
    modelValue: {
      type: Array,
      default: () => [], // lista de user_id seleccionados
    },
    keyName: {
      type: String,
      default: "id",
    },
    label: {
      type: String,
      default: "name",
    },
  },
  data() {
    return {
      showAssigneeBox: false,
      user_search: "",
    };
  },
  computed: {
    selected() {
      return this.modelValue;
    },
    filteredUsers() {
      return this.users.filter((u) =>
        u.user[this.label].toLowerCase().includes(this.user_search.toLowerCase())
      );
    },
  },
  methods: {
    toggleUser(userId, checked) {
      let updated;
      if (checked) {
        updated = [...this.selected, userId];
      } else {
        updated = this.selected.filter((id) => id !== userId);
      }
      this.$emit("update:modelValue", {id:userId, checked});
    },
  },
};
</script>
